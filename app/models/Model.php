<?php
	namespace App\Model;

	use Nette\Object;
	use Nette\Database\Context;

	/**
	 * Základní třída modelu
	 */

	class Db extends Object {
		public $database;

		/**
		 * Konstruktor pro připojení k db
		 */
		public function __construct(Context $database) {
			$this->database = $database;
		}

		/**
		 * Funkce pro získání všech stránek
		 */
		public function getPages() {
			return $this->database->table('pages');
		}

		/**
		 * Funkce pro získání všech modulů
		 */
		public function getModules() {
			return $this->database->table('modules');
		}

		/**
		 * Model pro získání modulů stránek
		 */
		public function getPagesModules() {
			return $this->database->table('pages_modules');
		}

		/**
		 * Model pro získání kategorií modulů
		 */
		public function getSections() {
			return $this->database->table('sections');
		}

		/**
		 * Model pro získání článků
		 */
		public function getArticles() {
			return $this->database->table('articles');
		}

		/**
		 * Model pro získání kategorií
		 */
		public function getCategories () {
			return $this->database->table('categories');
		}

		/**
		 * Model pro získání kategorií modulu
		 */
		public function getModulesCategories() {
			return $this->database->table('pages_modules_categories');
		}

		/**
		 * Model pro získání kategorií článků
		 */
		public function getArticlesCategories () {
			return $this->database->table('articles_categories');
		}

		public function getArticlesTags () {
			return $this->database->table('articles_tags');
		}

		/**
		 * Model pro získání textového pole
		 */
		public function getEditors () {
			return $this->database->table('editors');
		}

		public function getArticlesPages () {
			return $this->database->table('articles_pages');
		}
		
		/**
		 * Model pro získání uživatelů
		 */
		public function getUsers () {
			return $this->database->table('users');
		}

		public function getUsersData () {
			return $this->database->table('users_data');
		}

		public function getUsersCategories () {
			return $this->database->table('users_categories');
		}

		/**
		 * Model pro získání práv uživatelů;
		 */
		public function getUsersPrivileges () {
			return $this->database->table('users_privileges');
		}

		/**
		 * Model pro získání galerií
		 */
		public function getGalleries () {
			return $this->database->table('galleries');
		}

		public function getFilestores () {
			return $this->database->table('filestores');
		}

		/**
		 * Model pro získání obrázků galerií
		 */
		public function getGalleriesImages () {
			return $this->database->table('galleries_images');
		}

		public function getSectionsThumbs () {
			return $this->database->table('sections_thumbs');
		}

		public function getFilestoresFiles () {
			return $this->database->table('filestores_files');
		}

		public function getFilestoresFilesTags () {
			return $this->database->table('filestores_files_tags');
		}

		public function getGalleriesImagesTags () {
			return $this->database->table('galleries_images_tags');
		}

		public function getFilesTags () {
			return $this->database->table('files_tags');
		}

		public function getLanguages () {
			return $this->database->table('languages');
		}

		/**
		 * get all localizations
		 * @return \Nette\Database\Table\Selection
		 */
		public function getLocalization () {
			return $this->database->table('localization');
		}

		/**
		 * get all localizations texts
		 * @return \Nette\Database\Table\Selection
		 */
		public function getLocalizationText () {
			return $this->database->table('localization_text');
		}

		public function getSectionsFields () {
			return $this->database->table('sections_fields');
		}

		public function getSectionsTags () {
			return $this->database->table('sections_tags');
		}

		public function getProducts () {
			return $this->database->table('products');
		}

		public function getShopSettings () {
			return $this->database->table('shop_settings');
		}

		public function getShopMethods () {
			return $this->database->table('shop_methods');
		}

		public function getShopProperties () {
			return $this->database->table('shop_properties');
		}

		public function getShopMethodsRelations () {
			return $this->database->table('shop_methods_relations');
		}

		public function getProductsCategories () {
			return $this->database->table('products_categories');
		}

		public function getStatistics() {
			return $this->database->table('statistics');
		}

		public function getSeoKw () {
			return $this->database->table('seo_kw');
		}

		public function getProductsPrices () {
			return $this->database->table('products_prices');
		}

		public function getOrders () {
			return $this->database->table('orders');
		}

		public function getOrdersProducts () {
			return $this->database->table('orders_products');
		}

		public function getFilters () {
			return $this->database->table('filters');
		}

		public function getProductsProperties () {
			return $this->database->table('products_properties');
		}

		public function getProductsRelated () {
			return $this->database->table('products_related');
		}

		public function getEmails () {
			return $this->database->table('emails');
		}

		public function getEmailsContent () {
			return $this->database->table('emails_content');
		}

		public function getEmailsQueue () {
			return $this->database->table('emails_queue');
		}

		public function getEmailsAttachments () {
			return $this->database->table('emails_attachments');
		}

		public function getProductsDiscounts () {
			return $this->database->table('products_discounts');
		}

		public function getArticlesInserted ($sql) {
			return $this->database->table('('.$sql.') AS temp');
		}

		public function getProductsInserted ($ids, $expiration, $visibility = true) {
			if (!$visibility) {
				return $this->database->table('products_categories LEFT JOIN (SELECT * FROM products ORDER BY id DESC) AS p ON products_categories.products_id = p.products_id');
			}
			else return $this->database->table('products_categories LEFT JOIN (SELECT * FROM products ORDER BY id DESC) AS p ON products_categories.products_id = p.products_id WHERE p.products_id IN ('.$ids.')'.($visibility ? ' AND visibility=1 ' : ' ').'AND pid IS NULL AND trash=0'.($expiration ? ' AND expirationDateFrom <= "'.date("Y-m-d H:i:s").'" AND expirationDateTo >= "'.date("Y-m-d H:i:s").'" ' : ' ').' GROUP BY p.products_id');
		}

		public function getProductsRelatedInserted () {
			return $this->database->table('products_related LEFT JOIN (SELECT * FROM products ORDER BY id DESC) AS p ON products_related.products_id = p.products_id');
		}

		public function getUsersInserted ($id) {
			return $this->database->table('(SELECT users.*, u.categories_id AS visibility FROM users LEFT JOIN users_categories AS u ON users.id = u.users_id AND u.categories_id = '.$id.') AS temp');
		}

		public function getSettings () {
			return $this->database->table('settings');
		}

		/** BOOKINGS **/
		public function getBookingRooms(){
			return $this->database->table('booking_rooms');
		}

		public function getBookingObjects(){
			return $this->database->table('booking_objects');
		}

		public function getBooking(){
			return $this->database->table('booking');
		}

		public function getBookingBookings(){
			return $this->database->table('booking_bookings');
		}

		public function getBookingHours(){
			return $this->database->table('booking_hours');
		}

		public function getBookingPrices(){
			return $this->database->table('booking_prices');
		}

		public function getPosts () {
			return $this->database->table('posts');
		}

		/**
		 * get properties categories of category
		 */
		public function getCategoriesCategories () {
			return $this->database->table('categories_categories');
		}

		/**
		 * get all active heureka categories
		 */
		public function getCategoriesHeureka () {
			return $this->database->table('categories_heureka');
		}

		/**
		 * get all zbozi.cz categories
		 */
		public function getCategoriesZbozi () {
			return $this->database->table('categories_zbozi');
		}

		/**
		 * get all active google merchants categories
		 */
		public function getCategoriesMerchants () {
			return $this->database->table('categories_merchants');
		}


		public function getProductsFormGroups () {
			return $this->database->table('products_form_groups');
		}

		public function getFormGroups () {
			return $this->database->table('form_groups');
		}

		public function getFormGroupsFields () {
			return $this->database->table('form_groups_fields');
		}

		public function getFormFields () {
			return $this->database->table('form_fields');
		}
	}
