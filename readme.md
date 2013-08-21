# WordPress to Wardrobe Converter

To convert a WordPress xml file into Wardrobe install.

To install:

1. Drop the ConvertFromWordpress.php file into your app/commands directory of Wardrobe
2. Add the code below to ```start/artisan.php```
```
$post = new Wardrobe\Repositories\DbPostRepository();  
$user = new Wardrobe\Repositories\DbUserRepository();  
$artisan->add(new ConvertFromWordPressCommand($post, $user));  
```
2. Add ```"pixel418/markdownify": "dev-master"``` to the require entry on your composer.json file (used to convert WordPress html to markdown - not required if you choose not to convert to markdown)
3. Upload your .xml file from your WordPress export to your server.
4. Run the artisan command ```php artisan convert:wordpress path/to/file```
5. Answer the series of questions and then let artisan do the rest.

