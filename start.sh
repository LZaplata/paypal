mkdir -p temp temp/sessions log www/files/galleries www/files/galleries/originals www/files/files www/files/avatars www/webtemp www/adminModule/webtemp;
chmod 0777 temp/ log/ temp/sessions/ www/files/files/ www/files/galleries/ www/files/galleries/originals www/files/avatars www/webtemp www/adminModule/webtemp;
composer update;
cp -f vendor/others/SelectBox.php vendor/nette/forms/src/Forms/Controls/