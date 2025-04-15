/**
     * Set up all required database tables
     *
     * @since    1.0.0
     */
    public function setup_database() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Create artwork themes table
        $this->create_artwork_themes_table($charset_collate);
        
        // Create art styles table
        $this->create_art_styles_table($charset_collate);

        // Create categories table
        $this->create_categories_table($charset_collate);
        
        // Create users table
        $this->create_users_table($charset_collate);
        
        // Create social shares table
        $this->create_social_shares_table($charset_collate);
        
        // Create user sessions table
        $this->create_user_sessions_table($charset_collate);
        
        // Create user activity table
        $this->create_user_activity_table($charset_collate);
        
        // Create CLOE analytics tables
        $this->create_cloe_analytics_tables($charset_collate);
        
        // Create artwork statistics table
        $this->create_artwork_statistics_table($charset_collate);
        
        // Create Thorius tables
        $this->create_thorius_tables($charset_collate);
        
        // Create referrers tracking table
        $this->create_referrers_table();
        
        // Create artwork theme mapping table
        $this->create_artwork_theme_mapping_table($charset_collate);
        
        // Seed default data
        $this->seed_art_styles();
        $this->seed_categories();
        $this->seed_artwork_themes();
        $this->import_wordpress_users();

        // Create user events table
        $this->create_user_events_table($charset_collate);
    }

    /**
     * Create art styles table
     *
     * @since    1.0.0
     * @param    string    $charset_collate    Database charset and collation
     */
    private function create_art_styles_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_art_styles';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            style_name varchar(191) NOT NULL,
            style_slug varchar(191) NOT NULL,
            style_description text,
            parent_style_id bigint(20) unsigned DEFAULT NULL,
            visual_characteristics text,
            historical_period varchar(100) DEFAULT NULL,
            origin_region varchar(100) DEFAULT NULL,
            creation_date datetime DEFAULT CURRENT_TIMESTAMP,
            last_updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            popularity_score decimal(10,2) DEFAULT '0.00',
            trend_score decimal(10,2) DEFAULT '0.00',
            artwork_count int(11) DEFAULT '0',
            is_featured tinyint(1) DEFAULT '0',
            is_ai_generated tinyint(1) DEFAULT '0',
            PRIMARY KEY  (id),
            UNIQUE KEY style_slug (style_slug),
            KEY parent_style_id (parent_style_id),
            KEY popularity_score (popularity_score),
            KEY trend_score (trend_score),
            KEY is_featured (is_featured),
            KEY is_ai_generated (is_ai_generated)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Seed the art_styles table with initial data
     *
     * @since    1.0.0
     */
    private function seed_art_styles() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_art_styles';
        
        // Check if the table is empty
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        
        if ($count > 0) {
            return; // Table already has data, no need to seed
        }
        
        // List of common art styles to seed
        $art_styles = array(
            array(
                'style_name' => 'Impressionism',
                'style_slug' => 'impressionism',
                'style_description' => 'A 19th-century art movement characterized by small, thin, yet visible brush strokes, open composition, emphasis on accurate depiction of light, ordinary subject matter, and unusual visual angles.',
                'visual_characteristics' => 'Visible brush strokes, vibrant colors, open composition, emphasis on light and its changing qualities.',
                'historical_period' => '19th Century',
                'origin_region' => 'France',
                'popularity_score' => 9.5,
                'is_featured' => 1
            ),
            array(
                'style_name' => 'Cubism',
                'style_slug' => 'cubism',
                'style_description' => 'Early-20th-century avant-garde art movement that revolutionized European painting and sculpture by depicting subjects from multiple viewpoints simultaneously.',
                'visual_characteristics' => 'Geometric shapes, multiple viewpoints, fragmented forms, abstract representation.',
                'historical_period' => 'Early 20th Century',
                'origin_region' => 'France',
                'popularity_score' => 8.7,
                'is_featured' => 1
            ),
            array(
                'style_name' => 'Surrealism',
                'style_slug' => 'surrealism',
                'style_description' => 'A 20th-century avant-garde movement that sought to release the creative potential of the unconscious mind by juxtaposing irrational, bizarre imagery.',
                'visual_characteristics' => 'Dream-like scenes, unexpected juxtapositions, irrational elements, symbolic imagery.',
                'historical_period' => '20th Century',
                'origin_region' => 'Europe',
                'popularity_score' => 9.0,
                'is_featured' => 1
            ),
            array(
                'style_name' => 'Abstract Expressionism',
                'style_slug' => 'abstract-expressionism',
                'style_description' => 'Post-World War II art movement characterized by spontaneous creation, emotional intensity, and non-representational forms.',
                'visual_characteristics' => 'Gestural brush strokes, spontaneous mark-making, non-representational forms, large canvases.',
                'historical_period' => 'Mid-20th Century',
                'origin_region' => 'United States',
                'popularity_score' => 8.2,
                'is_featured' => 1
            ),
            array(
                'style_name' => 'Digital Surrealism',
                'style_slug' => 'digital-surrealism',
                'style_description' => 'A contemporary digital art style that combines surrealist concepts with digital techniques and tools.',
                'visual_characteristics' => 'Dream-like digital scenes, impossible physics, digital manipulation, futuristic elements.',
                'historical_period' => '21st Century',
                'origin_region' => 'Global',
                'popularity_score' => 9.3,
                'is_featured' => 1,
                'is_ai_generated' => 1
            ),
            array(
                'style_name' => 'Fractal Art',
                'style_slug' => 'fractal-art',
                'style_description' => 'An algorithmic art form created by calculating fractal objects and representing the calculation results as still images, animations, or media.',
                'visual_characteristics' => 'Self-similar patterns, infinite complexity, mathematical precision, vibrant colors.',
                'historical_period' => 'Contemporary',
                'origin_region' => 'Global',
                'popularity_score' => 7.8,
                'is_featured' => 0,
                'is_ai_generated' => 1
            ),
            array(
                'style_name' => 'AI Generated Art',
                'style_slug' => 'ai-generated-art',
                'style_description' => 'Art created with the assistance of artificial intelligence algorithms such as GANs, diffusion models, and neural networks.',
                'visual_characteristics' => 'Algorithm-influenced aesthetics, unpredictable combinations, unique textures, dreamlike qualities.',
                'historical_period' => '21st Century',
                'origin_region' => 'Global',
                'popularity_score' => 9.7,
                'is_featured' => 1,
                'is_ai_generated' => 1
            ),
            array(
                'style_name' => 'Neo-Renaissance',
                'style_slug' => 'neo-renaissance',
                'style_description' => 'A contemporary revival of Renaissance artistic techniques and themes, often with modern subjects or contexts.',
                'visual_characteristics' => 'Classical techniques, rich colors, detailed figures, balanced composition.',
                'historical_period' => 'Contemporary',
                'origin_region' => 'Global',
                'popularity_score' => 7.5,
                'is_featured' => 0
            ),
            array(
                'style_name' => 'Minimalism',
                'style_slug' => 'minimalism',
                'style_description' => 'A style characterized by extreme simplicity of form and a deliberate lack of expressive content.',
                'visual_characteristics' => 'Geometric forms, minimal color palette, clean lines, simplicity, negative space.',
                'historical_period' => 'Mid-20th Century to Present',
                'origin_region' => 'United States',
                'popularity_score' => 8.0,
                'is_featured' => 0
            ),
            array(
                'style_name' => 'Pop Art',
                'style_slug' => 'pop-art',
                'style_description' => 'Art movement that emerged in the 1950s that challenges traditions by including imagery from popular culture such as advertising, news, etc.',
                'visual_characteristics' => 'Bold colors, recognizable imagery, commercial techniques, irony, wit.',
                'historical_period' => 'Mid-20th Century',
                'origin_region' => 'United Kingdom and United States',
                'popularity_score' => 8.8,
                'is_featured' => 1
            )
        );
        
        // Insert styles into the database
        foreach ($art_styles as $style) {
            $wpdb->insert(
                $table_name,
                array(
                    'style_name' => $style['style_name'],
                    'style_slug' => $style['style_slug'],
                    'style_description' => $style['style_description'],
                    'visual_characteristics' => $style['visual_characteristics'],
                    'historical_period' => $style['historical_period'],
                    'origin_region' => $style['origin_region'],
                    'creation_date' => current_time('mysql'),
                    'last_updated' => current_time('mysql'),
                    'popularity_score' => $style['popularity_score'],
                    'trend_score' => rand(5, 10) / 10 * $style['popularity_score'],
                    'artwork_count' => rand(10, 100),
                    'is_featured' => $style['is_featured'],
                    'is_ai_generated' => isset($style['is_ai_generated']) ? $style['is_ai_generated'] : 0
                )
            );
        }
    }

    /**
     * Create categories table
     *
     * @since    1.0.0
     * @param    string    $charset_collate    Database charset and collation
     */
    private function create_categories_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_categories';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            category_name varchar(191) NOT NULL,
            category_slug varchar(191) NOT NULL,
            category_description text,
            parent_id bigint(20) unsigned DEFAULT NULL,
            popularity_score decimal(10,2) DEFAULT '0.00',
            category_icon varchar(100) DEFAULT NULL,
            category_color varchar(20) DEFAULT NULL,
            creation_date datetime DEFAULT CURRENT_TIMESTAMP,
            last_updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            display_order int(11) DEFAULT '0',
            is_featured tinyint(1) DEFAULT '0',
            is_active tinyint(1) DEFAULT '1',
            item_count int(11) DEFAULT '0',
            PRIMARY KEY  (id),
            UNIQUE KEY category_slug (category_slug),
            KEY parent_id (parent_id),
            KEY popularity_score (popularity_score),
            KEY is_featured (is_featured),
            KEY is_active (is_active),
            KEY display_order (display_order)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Seed the categories table with initial data
     *
     * @since    1.0.0
     */
    private function seed_categories() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_categories';
        
        // Check if the table is empty
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        
        if ($count > 0) {
            return; // Table already has data, no need to seed
        }
        
        // List of main marketplace categories to seed
        $categories = array(
            array(
                'category_name' => 'Digital Art',
                'category_slug' => 'digital-art',
                'category_description' => 'Digital artwork created using digital tools and software, including AI-generated art, digital paintings, and illustrations.',
                'category_icon' => 'monitor',
                'category_color' => '#3498db',
                'popularity_score' => 9.8,
                'display_order' => 1,
                'is_featured' => 1,
                'is_active' => 1
            ),
            array(
                'category_name' => 'Photography',
                'category_slug' => 'photography',
                'category_description' => 'Artistic photographs and photo manipulations, including landscape, portrait, abstract, and documentary photography.',
                'category_icon' => 'camera',
                'category_color' => '#2ecc71',
                'popularity_score' => 8.9,
                'display_order' => 2,
                'is_featured' => 1,
                'is_active' => 1
            ),
            array(
                'category_name' => 'Traditional Art',
                'category_slug' => 'traditional-art',
                'category_description' => 'Artwork created using traditional mediums such as oil, acrylic, watercolor, charcoal, and other physical mediums.',
                'category_icon' => 'palette',
                'category_color' => '#e74c3c',
                'popularity_score' => 8.5,
                'display_order' => 3,
                'is_featured' => 1,
                'is_active' => 1
            ),
            array(
                'category_name' => 'AI-Generated Art',
                'category_slug' => 'ai-generated-art',
                'category_description' => 'Artwork created with the assistance of artificial intelligence algorithms, including generative art, style transfers, and collaborative human-AI creations.',
                'category_icon' => 'chip',
                'category_color' => '#9b59b6',
                'popularity_score' => 9.6,
                'display_order' => 4,
                'is_featured' => 1,
                'is_active' => 1
            ),
            array(
                'category_name' => '3D Art',
                'category_slug' => '3d-art',
                'category_description' => 'Three-dimensional digital creations, including 3D models, sculpts, renderings, and virtual environments.',
                'category_icon' => 'cube',
                'category_color' => '#f39c12',
                'popularity_score' => 8.7,
                'display_order' => 5,
                'is_featured' => 1,
                'is_active' => 1
            ),
            array(
                'category_name' => 'Animation',
                'category_slug' => 'animation',
                'category_description' => 'Animated artwork, including GIFs, short animations, motion graphics, and animated sequences.',
                'category_icon' => 'film',
                'category_color' => '#1abc9c',
                'popularity_score' => 8.3,
                'display_order' => 6,
                'is_featured' => 0,
                'is_active' => 1
            ),
            array(
                'category_name' => 'Mixed Media',
                'category_slug' => 'mixed-media',
                'category_description' => 'Artwork that combines multiple mediums or techniques, including digital and traditional combinations.',
                'category_icon' => 'layers',
                'category_color' => '#34495e',
                'popularity_score' => 7.9,
                'display_order' => 7,
                'is_featured' => 0,
                'is_active' => 1
            ),
            array(
                'category_name' => 'Concept Art',
                'category_slug' => 'concept-art',
                'category_description' => 'Illustrative designs created to convey ideas for films, games, animation, or other media before final production.',
                'category_icon' => 'bulb',
                'category_color' => '#e67e22',
                'popularity_score' => 8.6,
                'display_order' => 8,
                'is_featured' => 0,
                'is_active' => 1
            ),
            array(
                'category_name' => 'Illustration',
                'category_slug' => 'illustration',
                'category_description' => 'Artistic visualizations created to represent a story, idea, or concept, often used in books, magazines, and other media.',
                'category_icon' => 'pencil',
                'category_color' => '#8e44ad',
                'popularity_score' => 8.8,
                'display_order' => 9,
                'is_featured' => 0,
                'is_active' => 1
            ),
            array(
                'category_name' => 'Abstract',
                'category_slug' => 'abstract',
                'category_description' => 'Non-representational artwork that uses shapes, colors, forms and gestural marks to achieve its effect without attempting to represent external reality.',
                'category_icon' => 'shapes',
                'category_color' => '#d35400',
                'popularity_score' => 8.2,
                'display_order' => 10,
                'is_featured' => 0,
                'is_active' => 1
            )
        );
        
        // Insert categories into the database
        foreach ($categories as $category) {
            $wpdb->insert(
                $table_name,
                array(
                    'category_name' => $category['category_name'],
                    'category_slug' => $category['category_slug'],
                    'category_description' => $category['category_description'],
                    'category_icon' => $category['category_icon'],
                    'category_color' => $category['category_color'],
                    'creation_date' => current_time('mysql'),
                    'last_updated' => current_time('mysql'),
                    'popularity_score' => $category['popularity_score'],
                    'display_order' => $category['display_order'],
                    'is_featured' => $category['is_featured'],
                    'is_active' => $category['is_active'],
                    'item_count' => rand(15, 150)
                )
            );
            
            // Get the ID of the just inserted category
            $parent_id = $wpdb->insert_id;
            
            // If this is Digital Art, add subcategories
            if ($category['category_slug'] === 'digital-art') {
                $digital_subcategories = array(
                    array(
                        'category_name' => 'Digital Painting',
                        'category_slug' => 'digital-painting',
                        'category_description' => 'Digitally created artwork that simulates traditional painting techniques.',
                        'category_icon' => 'brush',
                        'category_color' => '#3498db',
                        'popularity_score' => 8.7
                    ),
                    array(
                        'category_name' => 'Pixel Art',
                        'category_slug' => 'pixel-art',
                        'category_description' => 'Digital art created using pixel-by-pixel editing with limited color palettes.',
                        'category_icon' => 'grid',
                        'category_color' => '#3498db',
                        'popularity_score' => 7.8
                    ),
                    array(
                        'category_name' => 'Vector Art',
                        'category_slug' => 'vector-art',
                        'category_description' => 'Artwork created using vector-based tools and techniques, resulting in scalable graphics.',
                        'category_icon' => 'bezier',
                        'category_color' => '#3498db',
                        'popularity_score' => 8.3
                    )
                );
                
                foreach ($digital_subcategories as $subcategory) {
                    $wpdb->insert(
                        $table_name,
                        array(
                            'category_name' => $subcategory['category_name'],
                            'category_slug' => $subcategory['category_slug'],
                            'category_description' => $subcategory['category_description'],
                            'parent_id' => $parent_id,
                            'category_icon' => $subcategory['category_icon'],
                            'category_color' => $subcategory['category_color'],
                            'creation_date' => current_time('mysql'),
                            'last_updated' => current_time('mysql'),
                            'popularity_score' => $subcategory['popularity_score'],
                            'display_order' => rand(1, 10),
                            'is_featured' => 0,
                            'is_active' => 1,
                            'item_count' => rand(5, 50)
                        )
                    );
                }
            }
            
            // If this is AI-Generated Art, add subcategories
            if ($category['category_slug'] === 'ai-generated-art') {
                $ai_subcategories = array(
                    array(
                        'category_name' => 'Text-to-Image',
                        'category_slug' => 'text-to-image',
                        'category_description' => 'Artwork created using AI models that generate images from text descriptions.',
                        'category_icon' => 'text-image',
                        'category_color' => '#9b59b6',
                        'popularity_score' => 9.4
                    ),
                    array(
                        'category_name' => 'Style Transfer',
                        'category_slug' => 'style-transfer',
                        'category_description' => 'Images created by applying the style of one image to the content of another using AI.',
                        'category_icon' => 'switch',
                        'category_color' => '#9b59b6',
                        'popularity_score' => 8.9
                    ),
                    array(
                        'category_name' => 'GAN Art',
                        'category_slug' => 'gan-art',
                        'category_description' => 'Artwork created using Generative Adversarial Networks and similar generative models.',
                        'category_icon' => 'network',
                        'category_color' => '#9b59b6',
                        'popularity_score' => 8.7
                    )
                );
                
                foreach ($ai_subcategories as $subcategory) {
                    $wpdb->insert(
                        $table_name,
                        array(
                            'category_name' => $subcategory['category_name'],
                            'category_slug' => $subcategory['category_slug'],
                            'category_description' => $subcategory['category_description'],
                            'parent_id' => $parent_id,
                            'category_icon' => $subcategory['category_icon'],
                            'category_color' => $subcategory['category_color'],
                            'creation_date' => current_time('mysql'),
                            'last_updated' => current_time('mysql'),
                            'popularity_score' => $subcategory['popularity_score'],
                            'display_order' => rand(1, 10),
                            'is_featured' => 0,
                            'is_active' => 1,
                            'item_count' => rand(5, 50)
                        )
                    );
                }
            }
        }
    }

    /**
     * Create users table
     *
     * @since    1.0.0
     * @param    string    $charset_collate    Database charset and collation
     */
    private function create_users_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_users';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            display_name varchar(191) NOT NULL,
            avatar_url varchar(255) DEFAULT NULL,
            user_type enum('artist','collector','gallery','admin') DEFAULT 'collector',
            artist_verified tinyint(1) DEFAULT '0',
            bio text,
            social_links text,
            preferred_styles text,
            preferred_categories text,
            price_range varchar(50) DEFAULT NULL,
            activity_score decimal(10,2) DEFAULT '0.00',
            last_login datetime DEFAULT NULL,
            login_count int(11) DEFAULT '0',
            registration_date datetime DEFAULT CURRENT_TIMESTAMP,
            last_updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            onboarding_completed tinyint(1) DEFAULT '0',
            preferences longtext,
            behavior_data longtext,
            ranking_score decimal(10,2) DEFAULT '0.00',
            is_featured tinyint(1) DEFAULT '0',
            subscription_id bigint(20) unsigned DEFAULT NULL,
            subscription_status varchar(50) DEFAULT NULL,
            subscription_expiry datetime DEFAULT NULL,
            tola_balance decimal(20,8) DEFAULT '0.00000000',
            PRIMARY KEY  (id),
            UNIQUE KEY user_id (user_id),
            KEY user_type (user_type),
            KEY artist_verified (artist_verified),
            KEY activity_score (activity_score),
            KEY ranking_score (ranking_score),
            KEY is_featured (is_featured),
            KEY subscription_status (subscription_status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Create an index for improved search performance on display_name
        $wpdb->query("CREATE FULLTEXT INDEX IF NOT EXISTS display_name_fulltext ON $table_name (display_name)");
    }

    /**
     * Import existing WordPress users into the Vortex users table
     *
     * @since    1.0.0
     */
    private function import_wordpress_users() {
        global $wpdb;
        
        $vortex_users_table = $wpdb->prefix . 'vortex_users';
        
        // Check if there are any users in the vortex_users table
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $vortex_users_table");
        
        // If there are already users in the table, do not proceed with import
        if ($count > 0) {
            return;
        }
        
        // Get all WordPress users
        $users = get_users(array(
            'fields' => array('ID', 'display_name', 'user_registered')
        ));
        
        foreach ($users as $user) {
            // Check if user already exists in vortex_users
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $vortex_users_table WHERE user_id = %d",
                $user->ID
            ));
            
            if ($exists) {
                continue; // Skip if user already exists
            }
            
            // Determine user type based on WordPress role
            $user_data = get_userdata($user->ID);
            $user_type = 'collector'; // Default
            
            if (in_array('administrator', $user_data->roles)) {
                $user_type = 'admin';
            } elseif (in_array('vortex_artist', $user_data->roles) || in_array('author', $user_data->roles)) {
                $user_type = 'artist';
            }
            
            // Get avatar URL
            $avatar_url = get_avatar_url($user->ID, array('size' => 200));
            
            // Insert user into vortex_users table
            $wpdb->insert(
                $vortex_users_table,
                array(
                    'user_id' => $user->ID,
                    'display_name' => $user->display_name,
                    'avatar_url' => $avatar_url,
                    'user_type' => $user_type,
                    'artist_verified' => ($user_type === 'artist' && $user_type !== 'admin') ? 0 : 1,
                    'registration_date' => $user->user_registered,
                    'last_updated' => current_time('mysql'),
                    'onboarding_completed' => 0,
                    'activity_score' => 0.00,
                    'ranking_score' => 0.00,
                    'is_featured' => 0,
                    'tola_balance' => 50.00000000 // Give new users some initial TOLA tokens
                )
            );
        }
        
        // Log the import
        error_log('Vortex: WordPress users imported to vortex_users table.');
    }

    /**
     * Create social shares table
     *
     * @since    1.0.0
     * @param    string    $charset_collate    Database charset and collation
     */
    private function create_social_shares_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_social_shares';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned DEFAULT NULL,
            artwork_id bigint(20) unsigned NOT NULL,
            platform varchar(50) NOT NULL,
            share_url varchar(255) DEFAULT NULL,
            share_message text,
            share_date datetime DEFAULT CURRENT_TIMESTAMP,
            ip_address varchar(100) DEFAULT NULL,
            user_agent text,
            share_status varchar(20) DEFAULT 'completed',
            click_count int(11) DEFAULT '0',
            engagement_count int(11) DEFAULT '0',
            conversion_count int(11) DEFAULT '0',
            last_updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            metadata longtext,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY artwork_id (artwork_id),
            KEY platform (platform),
            KEY share_date (share_date),
            KEY share_status (share_status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Create index for platform+artwork_id combination
        $wpdb->query("CREATE INDEX IF NOT EXISTS platform_artwork ON $table_name (platform, artwork_id)");
    }

    /**
     * Create the referrers tracking table
     * 
     * @return boolean Success status
     */
    public function create_referrers_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $table_name = $wpdb->prefix . 'vortex_referrers';
        $table_created = false;
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $sql = "CREATE TABLE $table_name (
                visit_id bigint(20) NOT NULL AUTO_INCREMENT,
                user_id bigint(20) DEFAULT NULL,
                session_id varchar(255) DEFAULT NULL,
                referrer_url text,
                referrer_domain varchar(255) DEFAULT NULL,
                campaign_id bigint(20) DEFAULT NULL,
                visit_time datetime DEFAULT CURRENT_TIMESTAMP,
                page_url text,
                converted tinyint(1) DEFAULT 0,
                conversion_time datetime DEFAULT NULL,
                device_type varchar(50) DEFAULT NULL,
                browser varchar(50) DEFAULT NULL,
                ip_address varchar(50) DEFAULT NULL,
                metadata text,
                PRIMARY KEY (visit_id),
                KEY user_id (user_id),
                KEY campaign_id (campaign_id),
                KEY referrer_domain (referrer_domain),
                KEY visit_time (visit_time)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            // Verify table was created successfully
            $table_created = ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name);
        } else {
            $table_created = true; // Table already exists
        }
        
        // Also ensure the campaigns table exists
        $campaigns_table = $wpdb->prefix . 'vortex_campaigns';
        if ($wpdb->get_var("SHOW TABLES LIKE '$campaigns_table'") != $campaigns_table) {
            $sql = "CREATE TABLE $campaigns_table (
                campaign_id bigint(20) NOT NULL AUTO_INCREMENT,
                campaign_name varchar(255) NOT NULL,
                campaign_type varchar(50) DEFAULT NULL,
                start_date datetime DEFAULT CURRENT_TIMESTAMP,
                end_date datetime DEFAULT NULL,
                campaign_cost decimal(10,2) DEFAULT 0.00,
                campaign_budget decimal(10,2) DEFAULT 0.00,
                campaign_status varchar(20) DEFAULT 'active',
                target_audience text,
                utm_parameters text,
                created_by bigint(20) NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (campaign_id),
                KEY campaign_status (campaign_status),
                KEY created_by (created_by)
            ) $charset_collate;";
            
            dbDelta($sql);
        }
        
        return $table_created;
    }

    /**
     * Fix missing referrers table - can be called directly to fix database issues
     * 
     * @since    1.0.0
     * @return   boolean   Success status
     */
    public static function fix_missing_referrers_table() {
        $instance = self::get_instance();
        return $instance->create_referrers_table();
    }

    /**
     * Create user sessions database table
     */
    public static function create_user_sessions_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_user_sessions';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            session_id varchar(32) NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            start_time datetime NOT NULL,
            end_time datetime DEFAULT NULL,
            last_activity datetime DEFAULT NULL,
            duration int(11) DEFAULT 0,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            active tinyint(1) DEFAULT 1,
            PRIMARY KEY  (id),
            UNIQUE KEY session_id (session_id),
            KEY user_id (user_id),
            KEY active (active)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Create user events table
     *
     * @since    1.0.0
     * @param    string    $charset_collate    Database charset and collation
     */
    private function create_user_events_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_user_events';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            event_type varchar(50) NOT NULL,
            event_data longtext NOT NULL,
            timestamp datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY event_type (event_type),
            KEY timestamp (timestamp)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Performs database migrations for this version
     */
    public static function migrate_to_current_version() {
        global $wpdb;
        
        $db_version = get_option('vortex_db_version', '1.0.0');
        
        // Run migrations based on version
        if (version_compare($db_version, '1.1.0', '<')) {
            self::migrate_to_1_1_0();
            update_option('vortex_db_version', '1.1.0');
        }
        
        if (version_compare($db_version, '1.2.0', '<')) {
            self::migrate_to_1_2_0();
            update_option('vortex_db_version', '1.2.0');
        }
        
        if (version_compare($db_version, '1.3.0', '<')) {
            // Run the 1.3.0 migrations
            self::add_style_id_to_artworks();
            self::populate_artwork_style_ids();
            update_option('vortex_db_version', '1.3.0');
        }
        
        if (version_compare($db_version, '1.4.0', '<')) {
            // Run the 1.4.0 migrations
            self::ensure_transactions_table();
            update_option('vortex_db_version', '1.4.0');
        }
        
        if (version_compare($db_version, '1.5.0', '<')) {
            // Run the 1.5.0 migrations
            self::ensure_tags_table();
            self::ensure_artwork_tags_table();
            update_option('vortex_db_version', '1.5.0');
        }
        
        if (version_compare($db_version, '1.6.0', '<')) {
            // Run the 1.6.0 migrations
            self::ensure_searches_table();
            update_option('vortex_db_version', '1.6.0');
        }
        
        // Add activity_time column to the user sessions table if needed
        self::add_activity_time_to_sessions();
        
        // Ensure all critical tables exist
        self::ensure_critical_tables();
    }

    /**
     * Add activity_time column to user sessions table if it doesn't exist
     */
    private static function add_activity_time_to_sessions() {
        global $wpdb;
        $sessions_table = $wpdb->prefix . 'vortex_user_sessions';
        
        // Check if the table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$sessions_table'") !== $sessions_table) {
            return;
        }
        
        // Check if the activity_time column already exists
        $column_exists = false;
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $sessions_table");
        foreach ($columns as $column) {
            if ($column->Field === 'activity_time') {
                $column_exists = true;
                break;
            }
        }
        
        // Add the column if it doesn't exist
        if (!$column_exists) {
            $wpdb->query("ALTER TABLE $sessions_table ADD COLUMN activity_time datetime DEFAULT CURRENT_TIMESTAMP AFTER last_activity");
            $wpdb->query("ALTER TABLE $sessions_table ADD INDEX activity_time (activity_time)");
            
            // Initialize the activity_time for existing records
            $wpdb->query("UPDATE $sessions_table SET activity_time = last_activity WHERE last_activity IS NOT NULL");
            $wpdb->query("UPDATE $sessions_table SET activity_time = start_time WHERE activity_time IS NULL");
            
            error_log('Added activity_time column to ' . $sessions_table);
        }
    }

    /**
     * Add style_id column to artworks table
     * 
     * @return bool Success or failure
     */
    public static function add_style_id_to_artworks() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_artworks';
        $success = true;
        
        // Check if the table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            return false;
        }
        
        // Check if the column already exists
        $column_exists = false;
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
        foreach ($columns as $column) {
            if ($column->Field === 'style_id') {
                $column_exists = true;
                break;
            }
        }
        
        // Add the column if it doesn't exist
        if (!$column_exists) {
            // Add the style_id column
            $success = $wpdb->query("ALTER TABLE $table_name ADD COLUMN style_id bigint(20) unsigned DEFAULT NULL AFTER artist_id");
            
            // Add an index for performance
            if ($success !== false) {
                $wpdb->query("ALTER TABLE $table_name ADD INDEX style_id (style_id)");
            }
            
            if ($success !== false) {
                error_log('Added style_id column to ' . $table_name);
            } else {
                error_log('Failed to add style_id column to ' . $table_name);
            }
        }
        
        return $success !== false;
    }

    /**
     * Populate style_id column for existing artworks
     * 
     * @return int Number of artworks updated
     */
    public static function populate_artwork_style_ids() {
        global $wpdb;
        $artworks_table = $wpdb->prefix . 'vortex_artworks';
        $styles_table = $wpdb->prefix . 'vortex_art_styles';
        $count = 0;
        
        // Make sure both tables exist
        if ($wpdb->get_var("SHOW TABLES LIKE '$artworks_table'") !== $artworks_table) {
            return 0;
        }
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$styles_table'") !== $styles_table) {
            return 0;
        }
        
        // Check if style_id column exists in artworks table
        $column_exists = false;
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $artworks_table");
        foreach ($columns as $column) {
            if ($column->Field === 'style_id') {
                $column_exists = true;
                break;
            }
        }
        
        if (!$column_exists) {
            // Column doesn't exist, can't proceed
            return 0;
        }
        
        // Get all artworks that have NULL style_id
        $artworks = $wpdb->get_results("
            SELECT a.artwork_id, a.post_id 
            FROM $artworks_table a
            WHERE a.style_id IS NULL
        ");
        
        if (empty($artworks)) {
            return 0; // No artworks to update
        }
        
        // Loop through artworks and try to find style information
        foreach ($artworks as $artwork) {
            // Try to get style from post terms
            $style_id = null;
            $terms = wp_get_post_terms($artwork->post_id, 'ai_style');
            
            if (!empty($terms) && !is_wp_error($terms)) {
                $style_slug = $terms[0]->slug;
                
                // Look up style in the styles table
                $style_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $styles_table WHERE style_slug = %s",
                    $style_slug
                ));
            }
            
            // If we found a style, update the artwork
            if ($style_id) {
                $result = $wpdb->update(
                    $artworks_table,
                    array('style_id' => $style_id),
                    array('artwork_id' => $artwork->artwork_id),
                    array('%d'),
                    array('%d')
                );
                
                if ($result !== false) {
                    $count++;
                }
            }
        }
        
        if ($count > 0) {
            error_log("Updated style_id for $count artworks");
        }
        
        return $count;
    }

    /**
     * Ensure the transactions table exists with the required columns
     */
    public static function ensure_transactions_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_transactions';
        
        // Check if the table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        // Create the table if it doesn't exist
        if (!$table_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                transaction_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                order_id bigint(20) unsigned DEFAULT NULL,
                artwork_id bigint(20) unsigned DEFAULT NULL,
                buyer_id bigint(20) unsigned DEFAULT NULL,
                seller_id bigint(20) unsigned DEFAULT NULL,
                amount decimal(20,8) unsigned NOT NULL DEFAULT 0.00000000,
                transaction_fee decimal(20,8) unsigned DEFAULT 0.00000000,
                currency varchar(20) NOT NULL DEFAULT 'TOLA',
                transaction_hash varchar(255) DEFAULT NULL,
                blockchain varchar(50) DEFAULT NULL,
                status varchar(20) NOT NULL DEFAULT 'pending',
                payment_method varchar(50) DEFAULT NULL,
                transaction_type varchar(50) NOT NULL DEFAULT 'purchase',
                transaction_time datetime DEFAULT CURRENT_TIMESTAMP,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                notes text DEFAULT NULL,
                metadata text DEFAULT NULL,
                PRIMARY KEY (transaction_id),
                KEY order_id (order_id),
                KEY artwork_id (artwork_id),
                KEY buyer_id (buyer_id),
                KEY seller_id (seller_id),
                KEY status (status),
                KEY transaction_type (transaction_type),
                KEY transaction_time (transaction_time),
                KEY created_at (created_at),
                KEY transaction_hash (transaction_hash)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            return true;
        }
        
        // Check if the artwork_id column exists
        $column_exists = false;
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
        foreach ($columns as $column) {
            if ($column->Field === 'artwork_id') {
                $column_exists = true;
                break;
            }
        }
        
        // Add the artwork_id column if it doesn't exist
        if (!$column_exists) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN artwork_id bigint(20) unsigned DEFAULT NULL AFTER order_id");
            $wpdb->query("ALTER TABLE $table_name ADD INDEX artwork_id (artwork_id)");
            
            error_log('Added artwork_id column to ' . $table_name);
        }
        
        // Check if the transaction_time column exists
        $column_exists = false;
        foreach ($columns as $column) {
            if ($column->Field === 'transaction_time') {
                $column_exists = true;
                break;
            }
        }
        
        // Add the transaction_time column if it doesn't exist
        if (!$column_exists) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN transaction_time datetime DEFAULT CURRENT_TIMESTAMP AFTER transaction_type");
            $wpdb->query("ALTER TABLE $table_name ADD INDEX transaction_time (transaction_time)");
            
            // Initialize the transaction_time for existing records to match created_at
            $wpdb->query("UPDATE $table_name SET transaction_time = created_at WHERE transaction_time IS NULL");
            
            error_log('Added transaction_time column to ' . $table_name);
        }
        
        return true;
    }

    /**
     * Ensure the tags table exists
     */
    public static function ensure_tags_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_tags';
        
        // Check if the table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        // Create the table if it doesn't exist
        if (!$table_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                tag_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                tag_name varchar(100) NOT NULL,
                tag_slug varchar(100) NOT NULL,
                tag_description text DEFAULT NULL,
                parent_tag_id bigint(20) unsigned DEFAULT NULL,
                tag_type varchar(50) DEFAULT 'general',
                count int(11) DEFAULT 0,
                popularity_score decimal(10,2) DEFAULT 0.00,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (tag_id),
                UNIQUE KEY tag_slug (tag_slug),
                KEY parent_tag_id (parent_tag_id),
                KEY tag_type (tag_type),
                KEY popularity_score (popularity_score),
                KEY count (count)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            return true;
        }
        
        return true;
    }

    /**
     * Ensure the artwork_tags table exists
     */
    public static function ensure_artwork_tags_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_artwork_tags';
        
        // Check if the table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        // Create the table if it doesn't exist
        if (!$table_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                artwork_id bigint(20) unsigned NOT NULL,
                tag_id bigint(20) unsigned NOT NULL,
                confidence decimal(5,2) DEFAULT 1.00,
                added_by bigint(20) unsigned DEFAULT NULL,
                is_auto_generated tinyint(1) DEFAULT 0,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY artwork_tag (artwork_id, tag_id),
                KEY artwork_id (artwork_id),
                KEY tag_id (tag_id),
                KEY confidence (confidence),
                KEY is_auto_generated (is_auto_generated)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            return true;
        }
        
        return true;
    }

    /**
     * Ensure the searches table exists
     */
    public static function ensure_searches_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_searches';
        
        // Check if the table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        // Create the table if it doesn't exist
        if (!$table_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                user_id bigint(20) unsigned DEFAULT NULL,
                session_id varchar(32) DEFAULT NULL,
                search_query varchar(255) NOT NULL,
                search_time datetime DEFAULT CURRENT_TIMESTAMP,
                results_count int(11) DEFAULT 0,
                result_clicked tinyint(1) DEFAULT 0,
                clicked_position int(11) DEFAULT NULL,
                clicked_result_id bigint(20) unsigned DEFAULT NULL,
                search_filters text DEFAULT NULL,
                search_category varchar(50) DEFAULT NULL,
                search_location varchar(100) DEFAULT NULL,
                ip_address varchar(45) DEFAULT NULL,
                user_agent text DEFAULT NULL,
                search_page varchar(100) DEFAULT 'main',
                conversion tinyint(1) DEFAULT 0,
                PRIMARY KEY  (id),
                KEY user_id (user_id),
                KEY search_time (search_time),
                KEY search_query (search_query(191)),
                KEY search_category (search_category),
                KEY result_clicked (result_clicked),
                KEY conversion (conversion)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            error_log('Created vortex_searches table.');
            return true;
        }
        
        return true;
    }

    /**
     * Ensure all critical tables exist
     * This is a failsafe to prevent database errors
     */
    public static function ensure_critical_tables() {
        global $wpdb;
        
        // List of critical tables to check
        $critical_tables = array(
            'vortex_user_sessions' => 'add_activity_time_to_sessions',
            'vortex_artworks' => 'add_style_id_to_artworks',
            'vortex_art_styles' => 'ensure_art_styles_table',
            'vortex_transactions' => 'ensure_transactions_table',
            'vortex_tags' => 'ensure_tags_table',
            'vortex_artwork_tags' => 'ensure_artwork_tags_table',
            'vortex_searches' => 'ensure_searches_table',
            'vortex_artwork_themes' => 'ensure_artwork_themes_table',
            'vortex_artwork_theme_mapping' => 'ensure_artwork_theme_mapping_table',
            'vortex_search_transactions' => 'ensure_search_transactions_table',
            'vortex_cart_abandonment_feedback' => 'ensure_cart_abandonment_feedback_table',
            'vortex_search_artwork_clicks' => 'ensure_search_artwork_clicks_table',
            'vortex_search_results' => 'ensure_search_results_table',
            'vortex_social_hashtags' => 'ensure_social_hashtags_table',
            'vortex_hashtag_share_mapping' => 'ensure_hashtag_share_mapping_table'
        );
        
        // Check each critical table
        foreach ($critical_tables as $table => $method) {
            $table_name = $wpdb->prefix . $table;
            
            // Check if table exists
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
                // Call the appropriate method
                if (method_exists('Vortex_DB_Migrations', $method)) {
                    call_user_func(array('Vortex_DB_Migrations', $method));
                }
            }
        }
    }

    /**
     * Ensure the art_styles table exists
     */
    public static function ensure_art_styles_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_art_styles';
        
        // Check if the table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        // Create the table if it doesn't exist
        if (!$table_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                style_name varchar(100) NOT NULL,
                style_slug varchar(100) NOT NULL,
                style_description text DEFAULT NULL,
                parent_style_id bigint(20) unsigned DEFAULT NULL,
                visual_characteristics text DEFAULT NULL,
                historical_period varchar(100) DEFAULT NULL,
                origin_region varchar(100) DEFAULT NULL,
                creation_date datetime DEFAULT CURRENT_TIMESTAMP,
                last_updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                popularity_score decimal(5,2) DEFAULT 0.00,
                trend_score decimal(5,2) DEFAULT 0.00,
                artwork_count int(11) DEFAULT 0,
                is_featured tinyint(1) DEFAULT 0,
                is_ai_generated tinyint(1) DEFAULT 0,
                thumbnail_url varchar(255) DEFAULT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY style_slug (style_slug),
                KEY parent_style_id (parent_style_id),
                KEY popularity_score (popularity_score),
                KEY trend_score (trend_score),
                KEY is_featured (is_featured),
                KEY is_ai_generated (is_ai_generated)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            error_log('Created vortex_art_styles table.');
            return true;
        }
        
        return true;
    }

    /**
     * Seed the artwork_themes table with initial data
     *
     * @since    1.0.0
     */
    private function seed_artwork_themes() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_artwork_themes';
        
        // Check if the table is empty
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        
        if ($count > 0) {
            return; // Table already has data, no need to seed
        }
        
        // List of common artwork themes to seed
        $artwork_themes = array(
            array(
                'theme_name' => 'Nature',
                'theme_slug' => 'nature',
                'theme_description' => 'Artwork featuring natural landscapes, plants, animals, and other elements of the natural world.',
                'popularity_score' => 9.5,
                'is_featured' => 1
            ),
            array(
                'theme_name' => 'Abstract',
                'theme_slug' => 'abstract',
                'theme_description' => 'Non-representational artwork focusing on shapes, colors, forms, and gestural marks.',
                'popularity_score' => 8.7,
                'is_featured' => 1
            ),
            array(
                'theme_name' => 'Portrait',
                'theme_slug' => 'portrait',
                'theme_description' => 'Artwork depicting a person or a group of people, focusing on their faces and expressions.',
                'popularity_score' => 8.9,
                'is_featured' => 1
            ),
            array(
                'theme_name' => 'Urban',
                'theme_slug' => 'urban',
                'theme_description' => 'Cityscapes, architecture, and urban environments captured in various artistic styles.',
                'popularity_score' => 8.2,
                'is_featured' => 1
            ),
            array(
                'theme_name' => 'Fantasy',
                'theme_slug' => 'fantasy',
                'theme_description' => 'Artwork depicting imaginary worlds, mythical creatures, and magical elements.',
                'popularity_score' => 9.0,
                'is_featured' => 1
            ),
            array(
                'theme_name' => 'Science Fiction',
                'theme_slug' => 'science-fiction',
                'theme_description' => 'Futuristic scenes, space, technology, and other sci-fi elements.',
                'popularity_score' => 8.8,
                'is_featured' => 0
            ),
            array(
                'theme_name' => 'Still Life',
                'theme_slug' => 'still-life',
                'theme_description' => 'Artwork depicting inanimate objects arranged in a specific way.',
                'popularity_score' => 7.5,
                'is_featured' => 0
            ),
            array(
                'theme_name' => 'Surrealism',
                'theme_slug' => 'surrealism',
                'theme_description' => 'Artwork featuring unexpected juxtapositions and dreamlike imagery.',
                'popularity_score' => 8.6,
                'is_featured' => 1
            ),
            array(
                'theme_name' => 'Historical',
                'theme_slug' => 'historical',
                'theme_description' => 'Artwork depicting historical events, figures, or periods.',
                'popularity_score' => 7.8,
                'is_featured' => 0
            ),
            array(
                'theme_name' => 'Spiritual',
                'theme_slug' => 'spiritual',
                'theme_description' => 'Artwork with religious, mystical, or spiritual themes and symbolism.',
                'popularity_score' => 7.3,
                'is_featured' => 0
            )
        );
        
        // Insert themes into the database
        foreach ($artwork_themes as $theme) {
            $wpdb->insert(
                $table_name,
                array(
                    'theme_name' => $theme['theme_name'],
                    'theme_slug' => $theme['theme_slug'],
                    'theme_description' => $theme['theme_description'],
                    'creation_date' => current_time('mysql'),
                    'last_updated' => current_time('mysql'),
                    'popularity_score' => $theme['popularity_score'],
                    'trending_score' => rand(5, 10) / 10 * $theme['popularity_score'],
                    'artwork_count' => rand(10, 100),
                    'is_featured' => $theme['is_featured']
                )
            );
        }
    }

    /**
     * Create artwork theme mapping table
     *
     * @since    1.0.0
     * @param    string    $charset_collate    Database charset and collation
     */
    private function create_artwork_theme_mapping_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_artwork_theme_mapping';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            artwork_id bigint(20) unsigned NOT NULL,
            theme_id bigint(20) unsigned NOT NULL,
            relevance decimal(5,2) DEFAULT 1.00,
            added_by bigint(20) unsigned DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY artwork_theme (artwork_id, theme_id),
            KEY artwork_id (artwork_id),
            KEY theme_id (theme_id),
            KEY relevance (relevance)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Create artwork themes table
     *
     * @since    1.0.0
     * @param    string    $charset_collate    Database charset and collation
     */
    private function create_artwork_themes_table($charset_collate) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_artwork_themes';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            theme_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            theme_name varchar(191) NOT NULL,
            theme_slug varchar(191) NOT NULL,
            theme_description text,
            parent_id bigint(20) unsigned DEFAULT NULL,
            popularity_score decimal(10,2) DEFAULT '0.00',
            trending_score decimal(10,2) DEFAULT '0.00',
            creation_date datetime DEFAULT CURRENT_TIMESTAMP,
            last_updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            artwork_count int(11) DEFAULT '0',
            is_featured tinyint(1) DEFAULT '0',
            PRIMARY KEY  (theme_id),
            UNIQUE KEY theme_slug (theme_slug),
            KEY parent_id (parent_id),
            KEY popularity_score (popularity_score),
            KEY trending_score (trending_score),
            KEY is_featured (is_featured)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Ensure the artwork_themes table exists
     */
    public static function ensure_artwork_themes_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_artwork_themes';
        
        // Check if the table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        // Create the table if it doesn't exist
        if (!$table_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                theme_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                theme_name varchar(191) NOT NULL,
                theme_slug varchar(191) NOT NULL,
                theme_description text,
                parent_id bigint(20) unsigned DEFAULT NULL,
                popularity_score decimal(10,2) DEFAULT '0.00',
                trending_score decimal(10,2) DEFAULT '0.00',
                creation_date datetime DEFAULT CURRENT_TIMESTAMP,
                last_updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                artwork_count int(11) DEFAULT '0',
                is_featured tinyint(1) DEFAULT '0',
                PRIMARY KEY  (theme_id),
                UNIQUE KEY theme_slug (theme_slug),
                KEY parent_id (parent_id),
                KEY popularity_score (popularity_score),
                KEY trending_score (trending_score),
                KEY is_featured (is_featured)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            error_log('Created vortex_artwork_themes table.');
            
            // Seed the table with initial data
            self::seed_artwork_themes_data();
            
            return true;
        }
        
        return true;
    }

    /**
     * Ensure the artwork_theme_mapping table exists
     */
    public static function ensure_artwork_theme_mapping_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_artwork_theme_mapping';
        
        // Check if the table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        // Create the table if it doesn't exist
        if (!$table_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                artwork_id bigint(20) unsigned NOT NULL,
                theme_id bigint(20) unsigned NOT NULL,
                relevance decimal(5,2) DEFAULT 1.00,
                added_by bigint(20) unsigned DEFAULT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY artwork_theme (artwork_id, theme_id),
                KEY artwork_id (artwork_id),
                KEY theme_id (theme_id),
                KEY relevance (relevance)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            error_log('Created vortex_artwork_theme_mapping table.');
            return true;
        }
        
        return true;
    }
    
    /**
     * Seed the artwork_themes table with initial data
     */
    private static function seed_artwork_themes_data() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_artwork_themes';
        
        // Check if table already has data
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        if ($count > 0) {
            return;
        }
        
        // Initial themes data
        $themes = array(
            array(
                'theme_name' => 'Abstract',
                'theme_slug' => 'abstract',
                'theme_description' => 'Abstract artwork using shapes, forms, colors and lines to create non-representational compositions',
                'popularity_score' => 8.5,
                'is_featured' => 1
            ),
            array(
                'theme_name' => 'Landscape',
                'theme_slug' => 'landscape',
                'theme_description' => 'Natural or urban landscape scenes depicting environments and scenery',
                'popularity_score' => 8.9,
                'is_featured' => 1
            ),
            array(
                'theme_name' => 'Portrait',
                'theme_slug' => 'portrait',
                'theme_description' => 'Portraits of people, animals or other subjects focusing on likeness and character',
                'popularity_score' => 7.8,
                'is_featured' => 1
            ),
            array(
                'theme_name' => 'Surrealism',
                'theme_slug' => 'surrealism',
                'theme_description' => 'Dreamlike imagery that juxtaposes unexpected elements and challenges reality',
                'popularity_score' => 9.2,
                'is_featured' => 1
            ),
            array(
                'theme_name' => 'Minimalism',
                'theme_slug' => 'minimalism',
                'theme_description' => 'Simple, clean artwork that uses minimal elements to express ideas',
                'popularity_score' => 8.1,
                'is_featured' => 0
            ),
            array(
                'theme_name' => 'Fantasy',
                'theme_slug' => 'fantasy',
                'theme_description' => 'Imaginative scenes depicting magical worlds, creatures and concepts',
                'popularity_score' => 9.5,
                'is_featured' => 1
            ),
            array(
                'theme_name' => 'Sci-Fi',
                'theme_slug' => 'sci-fi',
                'theme_description' => 'Futuristic concepts exploring technology, space, and scientific advancements',
                'popularity_score' => 8.7,
                'is_featured' => 0
            ),
            array(
                'theme_name' => 'Nature',
                'theme_slug' => 'nature',
                'theme_description' => 'Plants, animals, natural elements and environments from our world',
                'popularity_score' => 9.0,
                'is_featured' => 1
            ),
            array(
                'theme_name' => 'Urban',
                'theme_slug' => 'urban',
                'theme_description' => 'City life, architecture, street scenes and urban environments',
                'popularity_score' => 7.9,
                'is_featured' => 0
            ),
            array(
                'theme_name' => 'Conceptual',
                'theme_slug' => 'conceptual',
                'theme_description' => 'Artwork that communicates ideas and concepts rather than concrete objects',
                'popularity_score' => 8.3,
                'is_featured' => 0
            )
        );
        
        // Insert data
        foreach ($themes as $theme) {
            $wpdb->insert(
                $table_name,
                array(
                    'theme_name' => $theme['theme_name'],
                    'theme_slug' => $theme['theme_slug'],
                    'theme_description' => $theme['theme_description'],
                    'popularity_score' => $theme['popularity_score'],
                    'trending_score' => $theme['popularity_score'] * (rand(80, 120) / 100), // Random trending score based on popularity
                    'artwork_count' => rand(5, 50), // Random initial count
                    'is_featured' => $theme['is_featured'],
                    'creation_date' => current_time('mysql'),
                    'last_updated' => current_time('mysql')
                )
            );
        }
        
        error_log('Seeded vortex_artwork_themes table with initial data.');
    }

    /**
     * Add click_count column to social shares table if it doesn't exist
     */
    public static function add_click_count_to_social_shares() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_social_shares';
        
        // Check if the table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            return false;
        }
        
        // Check if the click_count column already exists
        $column_exists = false;
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
        foreach ($columns as $column) {
            if ($column->Field === 'click_count') {
                $column_exists = true;
                break;
            }
        }
        
        // Add the column if it doesn't exist
        if (!$column_exists) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN click_count int(11) DEFAULT '0' AFTER share_status");
            error_log('Added click_count column to ' . $table_name);
            return true;
        }
        
        return false;
    }

    /**
     * Add transaction_time column to transactions table if it doesn't exist
     */
    public static function add_transaction_time_to_transactions() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_transactions';
        
        // Check if the table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            return false;
        }
        
        // Check if the transaction_time column already exists
        $column_exists = false;
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
        foreach ($columns as $column) {
            if ($column->Field === 'transaction_time') {
                $column_exists = true;
                break;
            }
        }
        
        // Add the column if it doesn't exist
        if (!$column_exists) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN transaction_time datetime DEFAULT CURRENT_TIMESTAMP AFTER transaction_type");
            $wpdb->query("ALTER TABLE $table_name ADD INDEX transaction_time (transaction_time)");
            
            // Initialize the transaction_time for existing records to match created_at
            $wpdb->query("UPDATE $table_name SET transaction_time = created_at WHERE transaction_time IS NULL");
            
            error_log('Added transaction_time column to ' . $table_name);
            return true;
        }
        
        return false;
    }

    /**
     * Ensure the cart_items table exists
     */
    public static function ensure_cart_items_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_cart_items';
        
        // Check if the table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        // Create the table if it doesn't exist
        if (!$table_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                cart_id bigint(20) unsigned NOT NULL,
                artwork_id bigint(20) unsigned NOT NULL,
                quantity int(11) unsigned NOT NULL DEFAULT 1,
                price decimal(20,8) unsigned NOT NULL DEFAULT 0.00000000,
                variation_id bigint(20) unsigned DEFAULT NULL,
                variation_data text DEFAULT NULL,
                added_date datetime DEFAULT CURRENT_TIMESTAMP,
                last_updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                custom_options text DEFAULT NULL,
                metadata text DEFAULT NULL,
                PRIMARY KEY (id),
                KEY cart_id (cart_id),
                KEY artwork_id (artwork_id),
                KEY variation_id (variation_id)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            error_log('Created vortex_cart_items table.');
            return true;
        }
        
        return true;
    }

    /**
     * Ensure the carts table exists
     */
    public static function ensure_carts_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_carts';
        
        // Check if the table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        // Create the table if it doesn't exist
        if (!$table_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                user_id bigint(20) unsigned DEFAULT NULL,
                session_id varchar(32) DEFAULT NULL,
                cart_token varchar(64) NOT NULL,
                created datetime DEFAULT CURRENT_TIMESTAMP,
                last_updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                cart_status varchar(20) DEFAULT 'active',
                cart_total decimal(10,2) DEFAULT 0.00,
                items_count int(11) DEFAULT 0,
                currency varchar(3) DEFAULT 'USD',
                converted_to_order tinyint(1) DEFAULT 0,
                order_id bigint(20) unsigned DEFAULT NULL,
                abandoned tinyint(1) DEFAULT 0,
                abandoned_time datetime DEFAULT NULL,
                recovery_email_sent tinyint(1) DEFAULT 0,
                recovery_email_time datetime DEFAULT NULL,
                recovered tinyint(1) DEFAULT 0,
                PRIMARY KEY (id),
                UNIQUE KEY cart_token (cart_token),
                KEY user_id (user_id),
                KEY cart_status (cart_status),
                KEY created (created),
                KEY last_updated (last_updated),
                KEY abandoned (abandoned),
                KEY recovered (recovered)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            error_log('Created vortex_carts table.');
            return true;
        }
        
        return true;
    }

    /**
     * Create or ensure the cart_abandonment_feedback table exists
     */
    public static function create_cart_abandonment_feedback() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_cart_abandonment_feedback';
        
        // Check if the table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        // Create the table if it doesn't exist
        if (!$table_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                cart_id bigint(20) unsigned NOT NULL,
                user_id bigint(20) unsigned DEFAULT NULL,
                session_id varchar(32) DEFAULT NULL,
                abandonment_time datetime DEFAULT CURRENT_TIMESTAMP,
                reason_category varchar(50) DEFAULT NULL,
                abandonment_reason varchar(50) DEFAULT NULL,
                reason_details text DEFAULT NULL,
                feedback_time datetime DEFAULT NULL,
                feedback_provided tinyint(1) DEFAULT 0,
                items_in_cart int(11) DEFAULT 0,
                cart_value decimal(10,2) DEFAULT 0.00,
                resolved tinyint(1) DEFAULT 0,
                resolution_notes text DEFAULT NULL,
                PRIMARY KEY  (id),
                KEY cart_id (cart_id),
                KEY user_id (user_id),
                KEY abandonment_time (abandonment_time),
                KEY reason_category (reason_category),
                KEY abandonment_reason (abandonment_reason),
                KEY feedback_provided (feedback_provided)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            error_log('Created vortex_cart_abandonment_feedback table.');
            return true;
        }
        
        // Make sure the table has all required columns, particularly abandonment_reason
        self::ensure_cart_abandonment_reason_column();
        
        return true;
    }

    /**
     * Ensure the cart_abandonment_feedback table has the abandonment_reason column
     */
    public static function ensure_cart_abandonment_reason_column() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_cart_abandonment_feedback';
        
        // Check if the table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            return false;
        }
        
        // Check if the abandonment_reason column already exists
        $column_exists = false;
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
        foreach ($columns as $column) {
            if ($column->Field === 'abandonment_reason') {
                $column_exists = true;
                break;
            }
        }
        
        // Add the column if it doesn't exist
        if (!$column_exists) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN abandonment_reason varchar(50) DEFAULT NULL AFTER reason_category");
            $wpdb->query("ALTER TABLE $table_name ADD INDEX abandonment_reason (abandonment_reason)");
            
            // Update existing records to set abandonment_reason equal to reason_category for backward compatibility
            $wpdb->query("UPDATE $table_name SET abandonment_reason = reason_category WHERE abandonment_reason IS NULL AND reason_category IS NOT NULL");
            
            error_log('Added abandonment_reason column to ' . $table_name);
            return true;
        }
        
        return false;
    }

    /**
     * Ensure the search_transactions table exists
     */
    public static function ensure_search_transactions_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_search_transactions';
        
        // Check if the table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        // Create the table if it doesn't exist
        if (!$table_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                search_id bigint(20) unsigned NOT NULL,
                transaction_id bigint(20) unsigned NOT NULL,
                relation_type varchar(50) DEFAULT 'direct',
                time_between_search_transaction int(11) DEFAULT 0,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                metadata text DEFAULT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY search_transaction (search_id, transaction_id),
                KEY search_id (search_id),
                KEY transaction_id (transaction_id),
                KEY relation_type (relation_type)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            error_log('Created vortex_search_transactions table.');
            return true;
        }
        
        return true;
    }

    /**
     * Ensure the search artwork clicks table exists
     */
    public static function ensure_search_artwork_clicks_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_search_artwork_clicks';
        
        // Check if the table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        // Create the table if it doesn't exist
        if (!$table_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                search_id bigint(20) unsigned NOT NULL,
                artwork_id bigint(20) unsigned NOT NULL,
                user_id bigint(20) unsigned DEFAULT NULL,
                session_id varchar(32) DEFAULT NULL,
                click_time datetime DEFAULT CURRENT_TIMESTAMP,
                click_position int(11) DEFAULT NULL,
                search_page varchar(100) DEFAULT 'main',
                result_type varchar(50) DEFAULT 'search',
                time_spent_viewing int(11) DEFAULT NULL,
                converted tinyint(1) DEFAULT 0,
                conversion_type varchar(50) DEFAULT NULL,
                conversion_value decimal(10,2) DEFAULT 0.00,
                conversion_time datetime DEFAULT NULL,
                ip_address varchar(45) DEFAULT NULL,
                user_agent text DEFAULT NULL,
                PRIMARY KEY  (id),
                KEY search_id (search_id),
                KEY artwork_id (artwork_id),
                KEY user_id (user_id),
                KEY click_time (click_time),
                KEY converted (converted),
                KEY click_position (click_position)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            return true;
        }
        
        return false;
    }

    /**
     * Ensure the search results table exists
     */
    public static function ensure_search_results_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_search_results';
        
        // Check if the table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        // Create the table if it doesn't exist
        if (!$table_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                search_id bigint(20) unsigned NOT NULL,
                result_type varchar(50) NOT NULL DEFAULT 'artwork',
                result_id bigint(20) unsigned NOT NULL,
                relevance_score decimal(5,2) DEFAULT 1.00,
                display_position int(11) DEFAULT NULL,
                style_id bigint(20) unsigned DEFAULT NULL,
                theme_id bigint(20) unsigned DEFAULT NULL,
                was_clicked tinyint(1) DEFAULT 0,
                click_position int(11) DEFAULT NULL,
                click_time datetime DEFAULT NULL,
                impression_time datetime DEFAULT CURRENT_TIMESTAMP,
                metadata text DEFAULT NULL,
                PRIMARY KEY  (id),
                KEY search_id (search_id),
                KEY result_type (result_type),
                KEY result_id (result_id),
                KEY style_id (style_id),
                KEY theme_id (theme_id),
                KEY was_clicked (was_clicked),
                KEY impression_time (impression_time)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            error_log('Created vortex_search_results table.');
            return true;
        }
        
        return false;
    }

    /**
     * Ensure the social hashtags table exists
     */
    public static function ensure_social_hashtags_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_social_hashtags';
        
        // Check if the table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        // Create the table if it doesn't exist
        if (!$table_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                hashtag_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                hashtag varchar(255) NOT NULL,
                category varchar(50) DEFAULT 'general',
                description text DEFAULT NULL,
                usage_count int(11) DEFAULT 0,
                engagement_score decimal(5,2) DEFAULT 0.00,
                first_used datetime DEFAULT CURRENT_TIMESTAMP,
                last_used datetime DEFAULT CURRENT_TIMESTAMP,
                is_trending tinyint(1) DEFAULT 0,
                is_featured tinyint(1) DEFAULT 0,
                is_blocked tinyint(1) DEFAULT 0,
                relevance_score decimal(5,2) DEFAULT 0.00,
                created_by bigint(20) unsigned DEFAULT NULL,
                PRIMARY KEY  (hashtag_id),
                UNIQUE KEY hashtag (hashtag(191)),
                KEY category (category),
                KEY usage_count (usage_count),
                KEY engagement_score (engagement_score),
                KEY is_trending (is_trending),
                KEY is_featured (is_featured),
                KEY is_blocked (is_blocked)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            error_log('Created vortex_social_hashtags table.');
            
            // Seed with some initial popular hashtags
            self::seed_social_hashtags_data();
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Ensure the hashtag share mapping table exists
     */
    public static function ensure_hashtag_share_mapping_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_hashtag_share_mapping';
        
        // Check if the table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        // Create the table if it doesn't exist
        if (!$table_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                hashtag_id bigint(20) unsigned NOT NULL,
                share_id bigint(20) unsigned NOT NULL,
                artwork_id bigint(20) unsigned DEFAULT NULL,
                user_id bigint(20) unsigned DEFAULT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                UNIQUE KEY hashtag_share (hashtag_id, share_id),
                KEY hashtag_id (hashtag_id),
                KEY share_id (share_id),
                KEY artwork_id (artwork_id),
                KEY user_id (user_id)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            error_log('Created vortex_hashtag_share_mapping table.');
            return true;
        }
        
        return false;
    }
    
    /**
     * Seed the social_hashtags table with initial data
     */
    private static function seed_social_hashtags_data() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_social_hashtags';
        
        // Check if table already has data
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        if ($count > 0) {
            return;
        }
        
        // Initial popular hashtags for art
        $hashtags = array(
            // Art general hashtags
            array('hashtag' => 'art', 'category' => 'general', 'description' => 'General art hashtag', 'is_featured' => 1),
            array('hashtag' => 'artist', 'category' => 'general', 'description' => 'For posts by artists', 'is_featured' => 1),
            array('hashtag' => 'artwork', 'category' => 'general', 'description' => 'For sharing artwork', 'is_featured' => 1),
            array('hashtag' => 'digitalart', 'category' => 'digital', 'description' => 'For digital artwork', 'is_featured' => 1),
            array('hashtag' => 'contemporaryart', 'category' => 'style', 'description' => 'Contemporary art style', 'is_featured' => 1),
            
            // AI Art hashtags
            array('hashtag' => 'aiart', 'category' => 'ai', 'description' => 'AI generated art', 'is_featured' => 1),
            array('hashtag' => 'generativeart', 'category' => 'ai', 'description' => 'Generative art', 'is_featured' => 1),
            array('hashtag' => 'stablediffusion', 'category' => 'ai', 'description' => 'Stable Diffusion generated art', 'is_featured' => 0),
            array('hashtag' => 'midjourney', 'category' => 'ai', 'description' => 'Midjourney generated art', 'is_featured' => 0),
            array('hashtag' => 'aiartist', 'category' => 'ai', 'description' => 'AI art creator', 'is_featured' => 0),
            
            // Style specific hashtags
            array('hashtag' => 'abstractart', 'category' => 'style', 'description' => 'Abstract art style', 'is_featured' => 1),
            array('hashtag' => 'impressionism', 'category' => 'style', 'description' => 'Impressionist art style', 'is_featured' => 0),
            array('hashtag' => 'surrealism', 'category' => 'style', 'description' => 'Surrealist art style', 'is_featured' => 1),
            array('hashtag' => 'minimalism', 'category' => 'style', 'description' => 'Minimalist art style', 'is_featured' => 0),
            array('hashtag' => 'popart', 'category' => 'style', 'description' => 'Pop art style', 'is_featured' => 0),
            
            // Marketplace specific
            array('hashtag' => 'vortexart', 'category' => 'marketplace', 'description' => 'Vortex Marketplace art', 'is_featured' => 1),
            array('hashtag' => 'vortexmarketplace', 'category' => 'marketplace', 'description' => 'Vortex Marketplace', 'is_featured' => 1),
            array('hashtag' => 'vortexartist', 'category' => 'marketplace', 'description' => 'Vortex Marketplace artist', 'is_featured' => 1),
            
            // Theme hashtags
            array('hashtag' => 'landscape', 'category' => 'theme', 'description' => 'Landscape art', 'is_featured' => 0),
            array('hashtag' => 'portrait', 'category' => 'theme', 'description' => 'Portrait art', 'is_featured' => 0),
            array('hashtag' => 'naturedrawing', 'category' => 'theme', 'description' => 'Nature drawings and paintings', 'is_featured' => 0),
            array('hashtag' => 'urbanart', 'category' => 'theme', 'description' => 'Urban themed art', 'is_featured' => 0),
            array('hashtag' => 'fantasyart', 'category' => 'theme', 'description' => 'Fantasy artwork', 'is_featured' => 1)
        );
        
        // Insert hashtags into the database
        foreach ($hashtags as $tag) {
            $wpdb->insert(
                $table_name,
                array(
                    'hashtag' => $tag['hashtag'],
                    'category' => $tag['category'],
                    'description' => $tag['description'],
                    'usage_count' => rand(10, 1000),
                    'engagement_score' => rand(50, 100) / 10,
                    'first_used' => date('Y-m-d H:i:s', strtotime('-' . rand(1, 30) . ' days')),
                    'last_used' => date('Y-m-d H:i:s', strtotime('-' . rand(0, 5) . ' days')),
                    'is_trending' => rand(0, 1),
                    'is_featured' => $tag['is_featured'],
                    'is_blocked' => 0,
                    'relevance_score' => rand(50, 100) / 10
                )
            );
        }
    }
} 