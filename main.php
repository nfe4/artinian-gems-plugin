<?php

/**
 * Plugin Name: Gems Products Inventory
 * Description: Update or insert new gems in wp products
 * Author: Netoptimize
 * Author URI: https://netzoptimize.com
 * Version: 1.0.0
 * Text Domain: gems-product-inventory
 */


if (!defined('ABSPATH')) {
    die('-1');
}

/**
 * Current version plugins
 */
if (!defined('GEMS_PRODUCT_VERSION')) {
    define('GEMS_PRODUCT_VERSION', '1.0.0');
}

// add in constant name path
defined('GEMS_PRODUCT_ROOT') or define('GEMS_PRODUCT_ROOT', dirname(__FILE__));
defined('GEMS_PRODUCT_URI') or define('GEMS_PRODUCT_URI', plugin_dir_url(__FILE__));

/*
* METHODS IN THE MAIN.PHP
*
* __construct()
*
* add_admin_pages()
* 
* admin_page_template()
*
* init()
*
* get_pano_gems()
*
* insert_pano_gem()
*
* gem_in_array()
*
* get_or_create_category_id()
*
* compare_sku_()
*
* get_product_id_by_sku()
* 
* img_to_db()
*
* downloadAttachmentPano()
*
* set_product_gallery()
*
* set_product_video()
*
* post_gems_into_db()
*
*
*/

if (!class_exists('GEMS_PRODUCTS_PLUGIN')) {
    class GEMS_PRODUCTS_PLUGIN
    {

        // GEMS_PRODUCTS_PLUGIN CLASS BEGIN

        /*
        * wp_pano_inventory records count
        *
        * @var
        *
        */
        public $count;


        /*
        * 
        * Array record for wp_panno_inventory records
        *
        * @var
        *
        */
        public $record;

        /*
        * Array Gems in Wordpress 
        *
        *
        */
        public $arrayGem = array();


        /*
        * 
        * Gem Log var
        *
        */
        public $gems_log_text;


        /*
        * 
        * Product Add 
        * Modify Count
        *  
        */
        public $modified_count;
        public $added_count;

        public $logs;


        public $processed_gems;





        /**
         * @param $error_msg
         *
         * error log function
         * $this->gems_log('error massage') -> exemple to use;
         *
         */
        function gems_log($error_msg)
        {
            $log_folder = plugin_dir_path(__FILE__) . '/error_logs';

            if (!is_dir($log_folder)) {
                mkdir($log_folder, 0755, true);
            }

            $log_file_name = $log_folder . "/error_logs_" . date("Y-m-d") . '.txt';
            $log_message = '[' . date('d-M-Y h:i:sa') . '] ' . $error_msg . "\n";

            file_put_contents($log_file_name, $log_message, FILE_APPEND);
        }




        function __construct()
        {
            //add menu tile to admin panel
            add_action('admin_menu', array($this, 'add_admin_pages'));

            include_once(ABSPATH . 'wp-admin/includes/plugin.php');

            require ABSPATH . 'wp-admin/includes/image.php';

            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $this->logs = "";

            $this->init();
        }



        //  Method 'add_admin_pages' creating a menu tile with name 
        //  to be shown in wp admin dashboard and fucntion admin_page_template
        function add_admin_pages()
        {
            add_menu_page('Gems Product Inventory', 'Gems Import', 'manage_options', 'gems_products_inventory', array($this, 'admin_page_template'), 'dashicons-superhero-alt', 50);
        }



        // Method 'conn' connects to extrnldb and return one wp_pano_record
        function conn()
        {

            $db = new mysqli('database',  'wordpress', 'wordpress',  'wordpress');

            // Fetch the records from the 'wp_pano_inventory' table
            $query = "SELECT * FROM wp_pano_inventory WHERE PANO_SKU = 9960";
            $result = $db->query($query);

            //get count of records 
            $this->count = $result->num_rows;
            // echo $count;

            $row = $result->fetch_assoc();
            $this->record  = $row;

            $record = $row;


            // Close the MySQL database connection
            $db->close();

            return $record;
        }



        // Method 'admin_page_template' having frontend template pages stored 
        // in template dir. for admin dashboard
        function admin_page_template()
        {


            //Recive actionprocess
            if (isset($_POST['actionprocess']) && $_POST['actionprocess'] == "insert_into_db") {

                $err = "<h3>Gem Import Request Recieved! </h3>";

                $this->logs = "Gem Import Request Recived.<br>";

                $this->gems_log('Gem Import Request Recived');

                $ans = $this->post_gems_into_db();
            }


            require_once GEMS_PRODUCT_ROOT .  '/template/frontpage.php';
        }



        function init()
        {

            add_action('rest_api_init', function () {

                register_rest_route('sync/', '/db', array(
                    'methods' => 'GET',
                    'callback' => array($this, 'post_gems_into_db'),

                ));
            });
        }




        //Method to get gems from the external db 'wp_pano_inventory' table
        public function get_pano_gems()
        {
            $this->logs .= "Connecting to database.<br>";

            $this->gems_log('Connecting to database');


            $db = new mysqli('database',  'wordpress', 'wordpress',  'wordpress');

            // Fetch the records from the 'wp_pano_inventory' table
            // $query = "SELECT * FROM wp_pano_inventory WHERE PANO_SKU = 17084";
            $query = "SELECT * FROM wp_pano_inventory ";
            $result = $db->query($query);

            //get count of records 
            $this->count = $result->num_rows;
            // echo $count;

            while ($row = $result->fetch_assoc()) {
                $records[]  = $row;
            }

            // Close the MySQL database connection
            $db->close();


            if (!empty($records)) {

                $this->logs .= "Gems Found in database.<br>";

                $this->gems_log('Gems Found in database');

                return $records;
            } else {

                $this->logs .= "No Gems Found in database.<br>";
                $this->logs .= "<span style='color:red;'>Aborting Gems Import.</span><br>";

                $this->gems_log('No Gems Found in database');
                $this->gems_log('Aborting Gems Import');

                return "";
            }
        }






        // Method to insert gem into wp_posts
        function insert_pano_gem($product)
        {

            $this->logs .= "Intiating Gem insertion with SKU: " . $product['PANO_SKU'] . ".<br>";

            $this->gems_log("Intiating Gem insertion with SKU: " . $product['PANO_SKU']);

            //fetching one record of gem
            // $gem = $this->conn();
            $gem = $product;

            //calling already in wp gem in array 
            $this->gem_in_array();

            //CODE TO DIVIDE PANO_SIZE INTO 3 DIMENSION
            $string = $gem['PANO_SIZE'];
            $parts = explode('x', $string);


            // wp_send_json($response);
            $length = $parts[0];  // '4.3'
            $width = $parts[1];   // '2.3'
            $height = $parts[2];  // '4.5'

            //processing the PANO_STATUS 
            $stock_status = $gem['PANO_STATUS'];

            $stock_status = $stock_status == 'In Stock' ? 'instock' : '';


            //getting gem category id
            $cat_id = $this->get_or_create_category_id($gem['PANO_CATEGORY']);


            // Define the product attribute options first
            $attribute_terms = array($gem['PANO_CUT'], $gem['PANO_SHAPE']);
            $attribute_cut_shape = implode('|', $attribute_terms);
            wp_set_object_terms(0, $attribute_cut_shape, 'pa_cut_shape');



            //insert product if not in db
            if ($this->compare_sku_($gem['PANO_SKU']) == 'insert') {

                $post_data = array(
                    'post_author'   => get_current_user_id(),
                    'post_title' => $gem['PANO_NAME'],
                    'post_content' => $gem['PANO_DESCRIPTION'],
                    'post_excerpt' => $gem['PANO_DETAILED_DESCRIPTION'],
                    'post_status' => 'publish',
                    'post_type' => 'product',
                    'post_category' => array($cat_id),
                    'meta_input' => array(
                        '_sku' => $gem['PANO_SKU'],
                        '_length' => $length,
                        '_width' => $width,
                        '_height' => $height,
                        '_weight' => $gem['PANO_WEIGHT'],
                        '_stock_status' => $stock_status,
                        '_regular_price' => $gem['PANO_CVMN_RETAIL_PRICE'],
                        '_price' => $gem['PANO_CVMN_RETAIL_PRICE'],

                        '_product_attributes' => array(
                            'pa_material' => array(
                                'name'         => 'pa_material',
                                'value'        => $gem['PANO_MATERIAL'],
                                'position'     => 0,
                                'is_visible'   => 1,
                                'is_variation' => 1,
                                'is_taxonomy'  => 1
                            ),
                            'pa_origin' => array(
                                'name'         => 'pa_origin',
                                'value'        => $gem['PANO_ORIGIN'],
                                'position'     => 1,
                                'is_visible'   => 1,
                                'is_variation' => 1,
                                'is_taxonomy'  => 1
                            ),
                            'pa_cut-shape' => array(
                                'name'         => 'Cut / shape',
                                'value'        => $attribute_cut_shape,
                                'position'     => 2,
                                'is_visible'   => 1,
                                'is_variation' => 1,
                                'is_taxonomy'  => 0
                            ),
                            'color' => array(
                                'name'         => 'Color',
                                'value'        => $gem['PANO_BASIC_COLOR'],
                                'position'     => 3,
                                'is_visible'   => 1,
                                'is_variation' => 1,
                                'is_taxonomy'  => 0
                            ),
                            'pa_treatment' => array(
                                'name'         => 'pa_treatment',
                                'value'        => $gem['PANO_TREATMENTS'],
                                'position'     => 4,
                                'is_visible'   => 1,
                                'is_variation' => 1,
                                'is_taxonomy'  => 1
                            ),
                            'certificate' => array(
                                'name'         => 'Certificate',
                                'value'        => $gem['PANO_CERTIFICATE'],
                                'position'     => 5,
                                'is_visible'   => 1,
                                'is_variation' => 1,
                                'is_taxonomy'  => 0
                            )
                        )


                    ),
                    'tax_input' => array(

                        'product_type' => 'simple',
                        'pa_material' => $gem['PANO_MATERIAL'],
                        'pa_origin' => $gem['PANO_ORIGIN'],
                        'pa_treatment' => $gem['PANO_TREATMENTS'],
                        'pa_cut_shape' => $gem['PANO_SHAPE'],
                        'pa_cut_shape' => $attribute_cut_shape,
                        'color' => $gem['PANO_BASIC_COLOR']
                    ),
                );

                $this->logs .= "Inserting Gem with SKU : " . $product['PANO_SKU'] . ".<br>";

                $this->gems_log("Inserting Gem with SKU : " . $product['PANO_SKU']);

                $this->added_count++;
            } else {

                //getting post_id value
                $post_id = $this->compare_sku_($gem['PANO_SKU']);

                //update product with the ID
                $post_data = array(
                    'post_author'   => get_current_user_id(),
                    'ID' => $post_id,
                    'post_title' => $gem['PANO_NAME'],
                    'post_content' => $gem['PANO_DESCRIPTION'],
                    'post_excerpt' => $gem['PANO_DETAILED_DESCRIPTION'],
                    'post_status' => 'publish',
                    'post_type' => 'product',
                    'post_category' => array($cat_id),
                    'meta_input' => array(
                        '_sku' => $gem['PANO_SKU'],
                        '_length' => $length,
                        '_width' => $width,
                        '_height' => $height,
                        '_weight' => $gem['PANO_WEIGHT'],
                        '_stock_status' => $stock_status,
                        '_regular_price' => $gem['PANO_CVMN_RETAIL_PRICE'],
                        '_price' => $gem['PANO_CVMN_RETAIL_PRICE'],

                        '_product_attributes' => array(
                            'pa_material' => array(
                                'name'         => 'pa_material',
                                'value'        => $gem['PANO_MATERIAL'],
                                'position'     => 0,
                                'is_visible'   => 1,
                                'is_variation' => 1,
                                'is_taxonomy'  => 1
                            ),
                            'pa_origin' => array(
                                'name'         => 'pa_origin',
                                'value'        => $gem['PANO_ORIGIN'],
                                'position'     => 1,
                                'is_visible'   => 1,
                                'is_variation' => 1,
                                'is_taxonomy'  => 1
                            ),
                            'pa_cut-shape' => array(
                                'name'         => 'Cut / shape',
                                'value'        => $attribute_cut_shape,
                                'position'     => 2,
                                'is_visible'   => 1,
                                'is_variation' => 1,
                                'is_taxonomy'  => 0
                            ),
                            'color' => array(
                                'name'         => 'Color',
                                'value'        => $gem['PANO_BASIC_COLOR'],
                                'position'     => 3,
                                'is_visible'   => 1,
                                'is_variation' => 1,
                                'is_taxonomy'  => 0
                            ),
                            'pa_treatment' => array(
                                'name'         => 'pa_treatment',
                                'value'        => $gem['PANO_TREATMENTS'],
                                'position'     => 4,
                                'is_visible'   => 1,
                                'is_variation' => 1,
                                'is_taxonomy'  => 1
                            ),
                            'certificate' => array(
                                'name'         => 'Certificate',
                                'value'        => $gem['PANO_CERTIFICATE'],
                                'position'     => 5,
                                'is_visible'   => 1,
                                'is_variation' => 1,
                                'is_taxonomy'  => 0
                            )
                        )


                    ),
                    'tax_input' => array(
                        'product_cat' => $gem['PANO_CATEGORY'],
                        'product_type' => 'simple',
                        'pa_material' => $gem['PANO_MATERIAL'],
                        'pa_origin' => $gem['PANO_ORIGIN'],
                        'pa_treatment' => $gem['PANO_TREATMENTS'],
                        'pa_cut_shape' => $attribute_cut_shape,
                        'color' => $gem['PANO_BASIC_COLOR']
                    ),
                );

                $this->logs .= "Updating Gem with SKU : " . $product['PANO_SKU'] . ".<br>";

                $this->gems_log("Updating Gem with SKU : " . $product['PANO_SKU']);

                $this->modified_count++;
            }


            $wp_error = "";


            if ($product_id = wp_insert_post($post_data, $wp_error)) {

                //SETTING PRODUCT CATEGORY
                wp_set_object_terms($product_id, $gem['PANO_CATEGORY'], 'product_cat');


                //POSTING PRODUCT THUMBNAIL
                if (!empty($gem['PANO_PHOTO1'])) {
                    if ($this->downloadAttachmentPano($gem['PANO_PHOTO1'], $product_id)) {



                        $this->logs .= "Setting Feature Image Gem with SKU " . $product['PANO_SKU'] . ".<br>";

                        $this->gems_log("Setting Feature Image Gem with SKU : " . $product['PANO_SKU']);

                        // echo "<br>image done<br>";
                    }
                }


                //POSTING PRODUCT GALLERY
                if (!empty($gem['PANO_PHOTO2'])) {

                    $this->set_product_gallery($gem['PANO_PHOTO2'], $product_id);

                    $this->logs .= "Setting Gem Gallery Image 1 For Gem with SKU : " . $product['PANO_SKU'] . ".<br>";

                    $this->gems_log("Setting Gem Gallery Image 1 For Gem with SKU : " . $product['PANO_SKU']);
                }

                if (!empty($gem['PANO_PHOTO3'])) {

                    $this->set_product_gallery($gem['PANO_PHOTO3'], $product_id);

                    $this->logs .= "Setting Gem Gallery Image 2 For Gem with SKU " . $product['PANO_SKU'] . ".<br>";

                    $this->gems_log("Setting Gem Gallery Image 2 For Gem with SKU " . $product['PANO_SKU']);
                }

                if (!empty($gem['PANO_PHOTO4'])) {

                    $this->set_product_gallery($gem['PANO_PHOTO4'], $product_id);

                    $this->logs .= "Setting Gem Gallery Image 3 For Gem with SKU " . $product['PANO_SKU'] . ".<br>";

                    $this->gems_log("Setting Gem Gallery Image 3 For Gem with SKU " . $product['PANO_SKU']);
                }


                //Inserting Videos 
                if (!empty($gem['PANO_VIDEO1'])) {

                    $this->logs .= "Setting Gem Video 1 Gem with SKU " . $product['PANO_SKU'] .  " Product ID: " . $product_id . ".<br>";

                    $this->gems_log("Setting Gem Video Gem 1 with SKU : " . $product['PANO_SKU'] .  " Product ID: " . $product_id);

                    $url = $this->set_product_video($gem['PANO_VIDEO1']);

                    update_post_meta($product_id, '_nickx_video_text_url', $url);
                    update_post_meta($product_id, '_nickx_product_video_type', 'nickx_video_url_local');
                    update_post_meta($product_id, '_custom_thumbnail', 0);
                    update_post_meta($product_id, '_nickx_product_video_thumb_ids', 0);
                    update_post_meta($product_id, '_video_schema', 0);
                    update_post_meta($product_id, '_nickx_video_upload_date', 0);
                    update_post_meta($product_id, '_nickx_video_name', 0);
                    update_post_meta($product_id, '_nickx_video_description', 0);


                    // Check if post was successfully inserted
                    if (is_wp_error($result)) {

                        $this->logs .= "ERROR Setting Gem Video 1 For Gem with SKU " . $product['PANO_SKU'] . ".<br>";

                        $this->gems_log("ERROR Setting Gem Video 1 For Gem with SKU " . $product['PANO_SKU']);
                    } else {
                        $this->logs .= "Successful Setting Gem Video 1 For Gem with SKU " . $product['PANO_SKU'] . ".<br>";

                        $this->gems_log("Successful Setting Gem Video 1 For Gem with SKU " . $product['PANO_SKU']);
                    }
                }


                //Inserting Videos 
                if (!empty($gem['PANO_VIDEO2'])) {

                    $this->logs .= "Setting Gem Video 2 Gem with SKU " . $product['PANO_SKU'] .  " Product ID: " . $product_id . ".<br>";

                    $this->gems_log("Setting Gem Video 2 Gem with SKU : " . $product['PANO_SKU'] .  " Product ID: " . $product_id);

                    $url = $this->set_product_video($gem['PANO_VIDEO1']);

                    update_post_meta($product_id, '_nickx_video_text_url', $url);
                    update_post_meta($product_id, '_nickx_product_video_type', 'nickx_video_url_local');
                    update_post_meta($product_id, '_custom_thumbnail', 0);
                    update_post_meta($product_id, '_nickx_product_video_thumb_ids', 0);
                    update_post_meta($product_id, '_video_schema', 0);
                    update_post_meta($product_id, '_nickx_video_upload_date', 0);
                    update_post_meta($product_id, '_nickx_video_name', 0);
                    update_post_meta($product_id, '_nickx_video_description', 0);


                    // Check if post was successfully inserted
                    if (is_wp_error($result)) {

                        $this->logs .= "ERROR Setting Gem Video 2 For Gem with SKU " . $product['PANO_SKU'] . ".<br>";

                        $this->gems_log("ERROR Setting Gem Video 2 For Gem with SKU " . $product['PANO_SKU']);
                    } else {
                        $this->logs .= "Successful Setting Gem Video 2 For Gem with SKU " . $product['PANO_SKU'] . ".<br>";

                        $this->gems_log("Successful Setting Gem Video 2 For Gem with SKU " . $product['PANO_SKU']);
                    }
                }



                //Inserting Videos 
                if (!empty($gem['PANO_VIDEO3'])) {

                    $this->logs .= "Setting Gem Video 3 Gem with SKU " . $product['PANO_SKU'] .  " Product ID: " . $product_id . ".<br>";

                    $this->gems_log("Setting Gem Video 3 Gem with SKU : " . $product['PANO_SKU'] .  " Product ID: " . $product_id);

                    $url = $this->set_product_video($gem['PANO_VIDEO1']);

                    update_post_meta($product_id, '_nickx_video_text_url', $url);
                    update_post_meta($product_id, '_nickx_product_video_type', 'nickx_video_url_local');
                    update_post_meta($product_id, '_custom_thumbnail', 0);
                    update_post_meta($product_id, '_nickx_product_video_thumb_ids', 0);
                    update_post_meta($product_id, '_video_schema', 0);
                    update_post_meta($product_id, '_nickx_video_upload_date', 0);
                    update_post_meta($product_id, '_nickx_video_name', 0);
                    update_post_meta($product_id, '_nickx_video_description', 0);


                    // Check if post was successfully inserted
                    if (is_wp_error($result)) {

                        $this->logs .= "ERROR Setting Gem Video 3 For Gem with SKU " . $product['PANO_SKU'] . ".<br>";

                        $this->gems_log("ERROR Setting Gem Video 3 For Gem with SKU " . $product['PANO_SKU']);
                    } else {
                        $this->logs .= "Successful Setting Gem Video 3 For Gem with SKU " . $product['PANO_SKU'] . ".<br>";

                        $this->gems_log("Successful Setting Gem Video 3 For Gem with SKU " . $product['PANO_SKU']);
                    }
                }


                //Inserting Videos 
                if (!empty($gem['PANO_VIDEO4'])) {

                    $this->logs .= "Setting Gem Video 4 Gem with SKU " . $product['PANO_SKU'] .  " Product ID: " . $product_id . ".<br>";

                    $this->gems_log("Setting Gem Video 4 Gem with SKU : " . $product['PANO_SKU'] .  " Product ID: " . $product_id);

                    $url = $this->set_product_video($gem['PANO_VIDEO1']);

                    update_post_meta($product_id, '_nickx_video_text_url', $url);
                    update_post_meta($product_id, '_nickx_product_video_type', 'nickx_video_url_local');
                    update_post_meta($product_id, '_custom_thumbnail', 0);
                    update_post_meta($product_id, '_nickx_product_video_thumb_ids', 0);
                    update_post_meta($product_id, '_video_schema', 0);
                    update_post_meta($product_id, '_nickx_video_upload_date', 0);
                    update_post_meta($product_id, '_nickx_video_name', 0);
                    update_post_meta($product_id, '_nickx_video_description', 0);


                    // Check if post was successfully inserted
                    if (is_wp_error($result)) {

                        $this->logs .= "ERROR Setting Gem Video 4 For Gem with SKU " . $product['PANO_SKU'] . ".<br>";

                        $this->gems_log("ERROR Setting Gem Video 4 For Gem with SKU " . $product['PANO_SKU']);
                    } else {
                        $this->logs .= "Successful Setting Gem Video 4 For Gem with SKU " . $product['PANO_SKU'] . ".<br>";

                        $this->gems_log("Successful Setting Gem Video 4 For Gem with SKU " . $product['PANO_SKU']);
                    }
                }




                $this->logs .= "<span style='color:green;' >Gem Insertion Successfull with SKU " . $product['PANO_SKU'] . " Product ID: " . $product_id . ".</span><br>";

                $this->gems_log("Gem Insertion Successfull with SKU : " . $product['PANO_SKU'] . " Product ID: " . $product_id);
            } else {

                $this->logs .= "<span style='color:red;' >Gem Insertion UNSuccessfull with SKU " . $product['PANO_SKU'] . ".</span><br>";

                $this->gems_log("Gem Insertion UNSuccessfull with SKU " . $product['PANO_SKU']);
            }
        }





        // Method 'gem_in_array' returns the array of already products in wordpress
        function gem_in_array()
        {

            // Get Products.
            $args = array(
                'post_type' => 'product',
                'posts_per_page' => -1
            );
            $query = new WP_Query($args);

            // Loop through products and display SKU and full product
            if ($query->have_posts()) {
                while ($query->have_posts()) {

                    $query->the_post();

                    $product = wc_get_product(get_the_ID());

                    $sku = $product->get_sku();

                    $data = $product->get_data();

                    $this->arrayGem[$sku] = $data;

                }
                wp_reset_postdata();
            }

            //arrayGem having elements as
            //  array_key   =>  array value
            //  sku         =>  product data

        }






        // Method to create / assign category to gem
        function get_or_create_category_id($cat)
        {

            $term = get_term_by('name', $cat, 'category');
            if ($term) {
                return $term->term_id;
            } else {
                $term = wp_insert_term($cat, 'category');
                if (is_wp_error($term)) {
                    return 0;
                } else {
                    return $term['term_id'];
                }
            }
        }






        //Method 'compare_sku_' for checking if product is in wp products or not
        //with the wp_pano_inventory => sku and already wp products => sku
        function compare_sku_(int $pano_sku)
        {

            $this->gem_in_array();

            // Accessing $data corresponding to $sku
            // if (isset($this->arrayGem[$pano_sku])) {
            //     $id = $this->arrayGem[$pano_sku]['id'];
            //     return $id;
            // } else {
            //     return "insert";
            // }

            if (in_array($pano_sku, array_keys($this->arrayGem))) {
                $id = $this->arrayGem[$pano_sku]['id'];
                return $id;
            } else {
                return "insert";
            }
        }




        //Get Product by SKU
        function get_product_id_by_sku($id)
        {

            $posts = get_posts(array(
                'meta_key'   => '_sku',
                'meta_value' => $id,
                'post_type'  => 'product',
            ));

            if (!empty($posts[0])) {
                return $posts[0];
            }

            return '';
        }





        function img_to_db($image_name)
        {

            // /Uploading image to the db
            $file_path = wp_upload_dir()['path'] . "/" . $image_name;

            $image_url = "https://artiniangems.com/wp-content/uploads/" . $image_name;

            $image_data = file_get_contents($image_url);

            file_put_contents($file_path, $image_data);
        }






        //Method to set thumbnail image into wp_posts
        function downloadAttachmentPano($image, $product_id)
        {

            $this->img_to_db($image);

            // Define the path to the image file
            $image_path = wp_upload_dir()['path'] . "/" . $image;

            // Get the upload directory information
            $upload_dir = wp_upload_dir();;

            // Define the filename for the image
            $image_name = basename($image_path);

            // Define the attachment data
            $attachment = array(
                'post_mime_type' => 'image/jpeg', // Replace with the MIME type of your image
                'post_title' => $image_name,
                'post_content' => '',
                'post_status' => 'inherit'
            );


            // Insert the attachment
            $attach_id = wp_insert_attachment($attachment, $image_path, $product_id);


            // Generate the metadata for the attachment
            $attach_data = wp_generate_attachment_metadata($attach_id, $image_path);

            // Update the attachment metadata
            wp_update_attachment_metadata($attach_id, $attach_data);

            // Set the featured image for the product
            if (set_post_thumbnail($product_id, $attach_id))

                return 1;

            else

                return 0;
        }





        //Method to add Product Gallery
        function set_product_gallery($image, $product_id)
        {


            // Define the path to the image file
            $image_path = wp_upload_dir()['path'] . "/" . $image;


            if (file_exists($image_path)) {

                return 1;
            } else {



                $this->img_to_db($image);

                // Get the upload directory information
                $upload_dir = wp_upload_dir();

                // Define the filename for the image
                $image_name = basename($image_path);

                // Define the attachment data
                $attachment = array(
                    'post_mime_type' => 'image/jpeg', // Replace with the MIME type of your image
                    'post_title' => $image_name,
                    'post_content' => '',
                    'post_status' => 'inherit'
                );

                // Insert the attachment
                $attach_id = wp_insert_attachment($attachment, $image_path, $product_id);

                // Generate the metadata for the attachment
                $attach_data = wp_generate_attachment_metadata($attach_id, $image_path);

                // Update the attachment metadata
                wp_update_attachment_metadata($attach_id, $attach_data);

                // Get the product gallery IDs
                $gallery_ids[] = get_post_meta($product_id, '_product_image_gallery', true);

                // Add the attachment ID to the gallery IDs
                $gallery_ids[] = $attach_id;

                // Update the product gallery
                if (update_post_meta($product_id, '_product_image_gallery', implode(',', $gallery_ids)))

                    return 1;

                else

                    return 0;
            }
        }







        // function set_product_video($video, $product_id)
        function set_product_video($video)
        {


            // Define the path to the video file
            $video_path = wp_upload_dir()['path'] . "/" . $video;

            if (file_exists($video_path)) {
                return wp_upload_dir()['url'] . "/" . $video;
            } else {



                $this->img_to_db($video);


                // Get the upload directory information
                $upload_dir = wp_upload_dir();

                // Define the filename for the video
                $video_name = basename($video_path);

                // Define the attachment data
                $attachment = array(
                    'post_mime_type' => 'video/mp4', // Replace with the MIME type of your video
                    'post_title' => $video_name,
                    'post_content' => '',
                    'post_status' => 'inherit'
                );

                // Insert the attachment
                // $attach_id = wp_insert_attachment($attachment, $video_path, $product_id);
                $attach_id = wp_insert_attachment($attachment, $video_path);

                // Generate the metadata for the attachment
                $attach_data = wp_generate_attachment_metadata($attach_id, $video_path);

                // Update the attachment metadata
                wp_update_attachment_metadata($attach_id, $attach_data);

                return wp_upload_dir()['url'] . "/" . $video;
            }

        }








        //Method to add Gems into DB
        function post_gems_into_db()
        {
            // update_option("import_gems_process", 'no');

            //Checking already running imports
            if (get_option("import_gems_process") && get_option("import_gems_process") == 'yes') {
                $date_c = date('Y-m-d H:i:s');

                $date_p = date('Y-m-d H:i:s');

                if (get_option("import_gems_process_time")) {
                    $date_p =  get_option("import_gems_process_time");
                }
                if (round((strtotime($date_c) - strtotime($date_p)) / 3600, 1) <= 2) {

                    $this->logs .= " One Import Already Running";

                    $this->gems_log(" One Import Already Running");

                    return 'Busy : One Import Already Running';
                }
            }

            update_option("import_gems_process", 'yes');
            update_option("import_gems_process_time", date('Y-m-d H:i:s'));

            $response = [];

            $this->logs .= "Starting Gems Importing. " . date("d-m-Y H:i:s", time()) . "<br>";

            $this->gems_log('Starting Gems Importing');

            $import_response = [];

            $removed_count = 0;
            $error_count = 0;

            $this->modified_count = 0;
            $this->added_count = 0;
            $this->processed_gems = 0;

            global $wpdb;

            $pano_gems = $this->get_pano_gems();

            if (empty($pano_gems)) {

                $this->gems_log_text .= "<br><h3> Gems Import Unsuccessfull. </h3></b>";

                $this->gems_log('Gems Import Unsuccessfull');

                return;
            }


            foreach ($pano_gems as $product) {

                $this->processed_gems++;

                //try
                try {

                    if (!empty($product['PANO_SKU'])) {

                        // echo "sku found";
                        $this->logs .= "Found Gem with SKU : " . $product['PANO_SKU'] . "<br>";
                        $this->logs .= "Processing Gem with SKU : " . $product['PANO_SKU'] . "<br>";

                        $this->gems_log("Found Gem with SKU : " . $product['PANO_SKU']);
                        $this->gems_log("Processing Gem with SKU : " . $product['PANO_SKU']);


                        if (empty($product['PANO_COMMAND'])) {

                            $this->logs .= "PANO_COMMAND : Command not Found : Gem SKU : " . $product['PANO_SKU'] . ".<br>";

                            // $this->gems_log('Command not found');

                            $this->gems_log("PANO_COMMAND : Command not Found, Gem SKU : " . $product['PANO_SKU']);
                        }

                        // remove product
                        if (!empty($product['PANO_COMMAND']) && $product['PANO_COMMAND'] == 'REMOVE') {

                            $wc_product = $this->get_product_id_by_sku($product['PANO_SKU']);

                            if (!empty($wc_product->ID)) {


                                $this->logs .= "Removing Gem with SKU : " . $product['PANO_SKU'] . ".<br>";

                                $this->gems_log("Removing Gem with SKU : " . $product['PANO_SKU']);

                                // delete post
                                wp_delete_post($wc_product->ID, true);


                                // echo 'delete done';

                                $this->logs .= "Gem Successfully Removed with SKU : " . $product['PANO_SKU'] . ".<br>";

                                $this->gems_log("Gem Successfully Removed with SKU : " . $product['PANO_SKU']);


                                $sku = $product['PANO_SKU'];

                                $query = "update `wp_pano_inventory` SET `PANO_COMMAND` = 'REMOVED' where `PANO_SKU` = $sku";

                                $upd = $wpdb->query($query);

                                $removed_count++;
                            } else {
                                $sku = $product['PANO_SKU'];

                                $query = "update `wp_pano_inventory` SET `PANO_COMMAND` = 'WARN:NOT FOUND' where `PANO_SKU` = $sku";

                                $upd = $wpdb->query($query);

                                $this->logs .= "Error Gem SKU " . $product['PANO_SKU'] . ", SKU not found: " . $sku . " COMMMAND: " . $product['PANO_COMMAND'] . " , Gem Not in WP-DB.<br>";

                                $error_count++;

                                $this->gems_log('SKU not found: ' . $sku . '; COMMMAND: ' . $product['PANO_COMMAND']);
                            }

                            continue;
                        }

                        //Add gem into DB
                        // if ($product['PANO_COMMAND'] == 'MODIFY' || $product['PANO_COMMAND'] == 'ADD' || $product['PANO_COMMAND'] == 'DONE') {
                        if ($product['PANO_COMMAND'] == 'MODIFY' || $product['PANO_COMMAND'] == 'ADD') {


                            $this->insert_pano_gem($product);
                        } else {
                            // echo "00";
                            //$this->gems_log( 'Error writing to database product by name ' . $fields['Name'] );
                            $command = $product['PANO_COMMAND'];

                            if ($command != 'REMOVED' && $command != 'DONE' && strpos($command, 'WARN') === false && strpos($command, 'ERROR') === false) {

                                $this->logs .= "<span style='color:red;' >Encounter Error with Gem having SKU " . $product['PANO_SKU'] . ".</span><br>";

                                $error_count++;

                                $this->gems_log('Unrecognized command: ' . $product['PANO_COMMAND'] . ' for sku: ' . $product['PANO_SKU']);
                            }

                            continue;
                        }
                    } else {
                        $this->gems_log("ERROR: Empty Product");
                    }

                    //try end catch general execp..
                } catch (Exception $e) {
                    $this->logs .= 'Encounter Unknown Error : ' . $e->getMessage() . "<br>";

                    $this->gems_log('Encounter Unknown Error : ' . $e->getMessage());
                }



                $this->gems_log("Gems Processed: " . $this->processed_gems);
                $this->gems_log("Gems Added: " . $this->added_count);
                $this->gems_log("Gems Modified: " . $this->modified_count);
                $this->gems_log("Gems Removed: " . $removed_count);

                //$this->gems_log( "<h3>Gems Added: " . $added_count . "</h3>");
                $this->gems_log_text .= "<br><h3>Gems Processed: " . $this->processed_gems . "</h3>";

                $this->gems_log_text .= "<br><h3>Gems Added: " . $this->added_count . "</h3>";

                //$this->gems_log( "<h3>Gems Modified: " . $modified_count . "</h3>");
                $this->gems_log_text .= "<br><h3>Gems Modified: " . $this->modified_count . "</h3>";

                //$this->gems_log( "<h3>Gems Removed: " . $removed_count . "</h3>");
                $this->gems_log_text .= "<br><h3>Gems Removed: " . $removed_count . "</h3>";

                //$this->gems_log( "<h3>Errors/warnings found: " . $error_count . "</h3>");
                $this->gems_log_text .= "<br><h3>Errors/warnings found: " . $error_count . "</h3>";
            }


            update_option("import_gems_process", 'no');

            if ("$_SERVER[REQUEST_URI]" == "/wp-json/sync/db/") {
                return [
                    "code" => "ok",
                    "message" => "Gems Imported successfully",
                    "data" => [
                        "status" => 200,
                    ]
                ];
            }
        }

        // GEMS_PRODUCTS_PLUGIN CLASS END
    }
}


$gem_inventory = new GEMS_PRODUCTS_PLUGIN();
