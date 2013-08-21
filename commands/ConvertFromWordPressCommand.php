<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Wardrobe\Repositories;

class ConvertFromWordPressCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'convert:wordpress';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Convert a WordPress XML file to Wardrobe';
    
    /**
	 * Post interface
	 *
	 * @var string
	 */
    protected $post;
    protected $user;
	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct($post, $user)
	{
		parent::__construct();
        $this->post = $post;
        $this->user = $user;
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
        $this->line('Welcome to WordPress to Wardrobe Converter');
        $filepath = $this->argument('filepath');
        
        if(File::exists($filepath)) {
            $this->line('Getting Information from '.$filepath);
        }
        else {
            $filepath = $this->error('File does not exist, try again');
            return;
        }
        
        $author = $this->confirm("Do you want to tranfer authors to Wardrobe? [yes|no] ", true);
        $cat_to_tags = $this->confirm("Do you want to transfer Categories to Tags? [yes|no] ", true);
        //$download = $this->confirm("Do you want to attempt to download attachments? [yes|no] ", true);
        $pages_to_posts = $this->confirm("Do you want to convert Pages to Posts? [yes|no] ", true);
        $markdown = $this->confirm("Do you want to convert HTML to markdown? [yes|no] ", true);
        
        if($author) {
            $this->line('Transferring Authors...');
        }
        else {
            $this->line('Authors not transfered.');
        }
        
        if($cat_to_tags) {
            $this->line('Transferring Categories to Tags...');
        }
        else {
            $this->line('Categories are not being transfered to wardrobe.');
        }
        
        /*if($download) {
            $this->line('Atempting to download attachments and store...');
        }
        else {
            $this->line('Attachments not download.');
        }*/
        
        if($pages_to_posts) {
            $this->line('Converting Pages to Posts...');
        }
        else {
            $this->line('Pages are not being transfered to wardrobe.');
        }
        
        if($markdown) {
            $this->line('Converting HTML to markdown...');
        }
        else {
            $this->line('HTML is not being converted to markdown.');
        }
        
        $xml = simplexml_load_file($filepath);
        $namspaces = $xml->getNamespaces(true);
        
        $authors = array();
        if($author) {
            foreach($xml->channel->children('wp',true)->wp_author as $value) {
                $name = $value->children('wp',true)->author_login;
                $email = $value->children('wp',true)->author_email;
                $this->line('Adding '.$name. ' with password: P@ssw0rd');
                $user = $this->user;
                $user = $user->create($name, '', $email, 1, 'P@ssw0rd');
                $authors[(string) $name] = array('name' => $name, 'email' => $email, 'id' => $user->id);
            }
        }
        
        foreach($xml->channel->item as $item) {
            $post_type = $item->children('wp',true)->post_type;
            /*if($post_type == 'attachment' and $download) {
                $this->line('Attempting to download file: '.$item->title);
            }
            else*/
            if($post_type == 'page' and $pages_to_posts) {
                $this->line('Converting Page to Post: '.$item->title);
                $title = $item->title;
                $slug = $item->children('wp',true)->post_name;
                $date = $item->children('wp',true)->post_date_gmt;
                $tags = array('Old Pages');
                $user = 1;
                if($author) {
                    $user = $authors[(string) $item->children('dc',true)->creator]['id'];
                }
                if($markdown) {
                    $this->line('Converting to Markdown.');
                    $content = $this->convertToMarkdown($item->children('content',true)->encoded);
                }
                else {
                    $content = $item->children('content',true)->encoded;                    
                }
                $post = $this->post;
                $post->create($title, $content, $slug, $tags, 1, $user, new DateTime($date));
            }
            else if($post_type == 'post'){
                $this->line('Adding Post: '.$item->title);
                $title = $item->title;
                $slug = $item->children('wp',true)->post_name;
                $active = 1;
                if($slug == '') {
                    $active = 0;
                }
                
                $date = $item->children('wp',true)->post_date_gmt;
                $tags = array();
                foreach($item as $key => $value) {
                    foreach($item->$key->attributes() as $i => $j) {
                        if($i == 'domain' && $j == 'post_tag') {
                            // add tag
                            $tags[] = $value;
                        }
                        if($i == 'domain' && $j == 'post_category' && $cat_to_tags) {
                            // convert categories to tags
                            $tags[] = $value;
                        }
                    }
                }
                $tags = array_unique($tags);
                $user = 1;
                if($author) {
                    $user = $authors[(string) $item->children('dc',true)->creator]['id'];
                }
                if($markdown) {
                    $this->line('Converting to Markdown.');
                    $content = $this->convertToMarkdown($item->children('content',true)->encoded);
                }
                else {
                    $content = $item->children('content',true)->encoded;
                }
                $post = $this->post;
                $post->create($title, $content, $slug, $tags, $active, $user, new DateTime($date));
            }
        }
        $this->line('Conversion complete.  Old links to internal site will not work if you delete them.  They need to be manually fixed.');
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			array('filepath', InputArgument::REQUIRED, 'The path where the WordPress XML file is located'),
		);
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	/*protected function getOptions()
	{
		return array(
			array('example', null, InputOption::VALUE_OPTIONAL, 'An example option.', null),
		);
	}*/
    
    function convertToMarkdown($string)
    {   
        $converter = new Markdownify\Converter;
        return $converter->parseString($string);
    }

}