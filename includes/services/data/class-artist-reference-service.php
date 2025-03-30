<?php
/**
 * Artist Reference Service
 *
 * @package VortexAiAgents
 * @subpackage Services\Data
 */

namespace VortexAiAgents\Services\Data;

use VortexAiAgents\Services\Cache_Service;

/**
 * Service for retrieving and managing artist reference data
 */
class Artist_Reference_Service {
    /**
     * Cache service instance
     *
     * @var Cache_Service
     */
    private $cache_service;

    /**
     * Constructor
     */
    public function __construct() {
        $this->cache_service = new Cache_Service( 'artist_references', 24 * HOUR_IN_SECONDS );
    }

    /**
     * Get all available artist references
     *
     * @return array Array of artist references with metadata
     */
    public function get_all_artists() {
        $cached_artists = $this->cache_service->get( 'all_artists' );
        
        if ( $cached_artists ) {
            return $cached_artists;
        }
        
        $artists = $this->fetch_artists();
        $this->cache_service->set( 'all_artists', $artists );
        
        return $artists;
    }

    /**
     * Get artist by ID
     *
     * @param string $artist_id Artist identifier.
     * @return array|false Artist data or false if not found
     */
    public function get_artist( $artist_id ) {
        $artists = $this->get_all_artists();
        
        foreach ( $artists as $artist ) {
            if ( $artist['id'] === $artist_id ) {
                return $artist;
            }
        }
        
        return false;
    }

    /**
     * Get artists by era
     *
     * @param string $era Era name.
     * @return array Filtered artists
     */
    public function get_artists_by_era( $era ) {
        $artists = $this->get_all_artists();
        $filtered = array();
        
        foreach ( $artists as $artist ) {
            if ( isset( $artist['era'] ) && $artist['era'] === $era ) {
                $filtered[] = $artist;
            }
        }
        
        return $filtered;
    }

    /**
     * Get artists by style
     *
     * @param string $style Style name.
     * @return array Filtered artists
     */
    public function get_artists_by_style( $style ) {
        $artists = $this->get_all_artists();
        $filtered = array();
        
        foreach ( $artists as $artist ) {
            if ( isset( $artist['styles'] ) && in_array( $style, $artist['styles'], true ) ) {
                $filtered[] = $artist;
            }
        }
        
        return $filtered;
    }

    /**
     * Get artists by medium
     *
     * @param string $medium Medium name.
     * @return array Filtered artists
     */
    public function get_artists_by_medium( $medium ) {
        $artists = $this->get_all_artists();
        $filtered = array();
        
        foreach ( $artists as $artist ) {
            if ( isset( $artist['mediums'] ) && in_array( $medium, $artist['mediums'], true ) ) {
                $filtered[] = $artist;
            }
        }
        
        return $filtered;
    }

    /**
     * Get unique eras from all artists
     *
     * @return array List of unique eras
     */
    public function get_eras() {
        $artists = $this->get_all_artists();
        $eras = array();
        
        foreach ( $artists as $artist ) {
            if ( isset( $artist['era'] ) && ! in_array( $artist['era'], $eras, true ) ) {
                $eras[] = $artist['era'];
            }
        }
        
        sort( $eras );
        return $eras;
    }

    /**
     * Get unique styles from all artists
     *
     * @return array List of unique styles
     */
    public function get_styles() {
        $artists = $this->get_all_artists();
        $styles = array();
        
        foreach ( $artists as $artist ) {
            if ( isset( $artist['styles'] ) && is_array( $artist['styles'] ) ) {
                foreach ( $artist['styles'] as $style ) {
                    if ( ! in_array( $style, $styles, true ) ) {
                        $styles[] = $style;
                    }
                }
            }
        }
        
        sort( $styles );
        return $styles;
    }

    /**
     * Get unique mediums from all artists
     *
     * @return array List of unique mediums
     */
    public function get_mediums() {
        $artists = $this->get_all_artists();
        $mediums = array();
        
        foreach ( $artists as $artist ) {
            if ( isset( $artist['mediums'] ) && is_array( $artist['mediums'] ) ) {
                foreach ( $artist['mediums'] as $medium ) {
                    if ( ! in_array( $medium, $mediums, true ) ) {
                        $mediums[] = $medium;
                    }
                }
            }
        }
        
        sort( $mediums );
        return $mediums;
    }

    /**
     * Search artists by keyword
     *
     * @param string $keyword Search keyword.
     * @return array Matching artists
     */
    public function search_artists( $keyword ) {
        $artists = $this->get_all_artists();
        $results = array();
        $keyword = strtolower( $keyword );
        
        foreach ( $artists as $artist ) {
            // Search in name, bio, and keywords
            if ( 
                strpos( strtolower( $artist['name'] ), $keyword ) !== false ||
                ( isset( $artist['bio'] ) && strpos( strtolower( $artist['bio'] ), $keyword ) !== false ) ||
                ( isset( $artist['keywords'] ) && $this->keywords_match( $artist['keywords'], $keyword ) )
            ) {
                $results[] = $artist;
            }
        }
        
        return $results;
    }

    /**
     * Check if any keywords match the search term
     *
     * @param array  $keywords Array of keywords.
     * @param string $search Search term.
     * @return bool True if any keyword matches
     */
    private function keywords_match( $keywords, $search ) {
        foreach ( $keywords as $keyword ) {
            if ( strpos( strtolower( $keyword ), $search ) !== false ) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get artist recommendations based on input parameters
     *
     * @param array $params Parameters for recommendation.
     * @return array Recommended artists
     */
    public function get_artist_recommendations( $params ) {
        $artists = $this->get_all_artists();
        $scored_artists = array();
        
        // Default parameters
        $default_params = array(
            'style' => '',
            'era' => '',
            'medium' => '',
            'mood' => '',
            'subject' => '',
        );
        
        $params = wp_parse_args( $params, $default_params );
        
        foreach ( $artists as $artist ) {
            $score = $this->calculate_artist_match_score( $artist, $params );
            
            if ( $score > 0 ) {
                $scored_artists[] = array(
                    'artist' => $artist,
                    'score' => $score,
                );
            }
        }
        
        // Sort by score (highest first)
        usort( $scored_artists, function( $a, $b ) {
            return $b['score'] <=> $a['score'];
        } );
        
        // Return top artists with scores
        $recommendations = array();
        foreach ( $scored_artists as $item ) {
            $recommendations[] = array(
                'id' => $item['artist']['id'],
                'name' => $item['artist']['name'],
                'bio' => isset( $item['artist']['bio'] ) ? $item['artist']['bio'] : '',
                'match_score' => $item['score'],
                'thumbnail' => isset( $item['artist']['thumbnail'] ) ? $item['artist']['thumbnail'] : '',
                'era' => isset( $item['artist']['era'] ) ? $item['artist']['era'] : '',
                'styles' => isset( $item['artist']['styles'] ) ? $item['artist']['styles'] : array(),
            );
        }
        
        return array_slice( $recommendations, 0, 5 ); // Return top 5
    }

    /**
     * Calculate match score between artist and parameters
     *
     * @param array $artist Artist data.
     * @param array $params Parameters to match against.
     * @return float Match score (0.0 to 1.0)
     */
    private function calculate_artist_match_score( $artist, $params ) {
        $score = 0;
        $factors = 0;
        
        // Match style
        if ( ! empty( $params['style'] ) && isset( $artist['styles'] ) && is_array( $artist['styles'] ) ) {
            $style_match = in_array( strtolower( $params['style'] ), array_map( 'strtolower', $artist['styles'] ), true );
            $score += $style_match ? 1 : 0;
            $factors++;
        }
        
        // Match era
        if ( ! empty( $params['era'] ) && isset( $artist['era'] ) ) {
            $era_match = ( strtolower( $params['era'] ) === strtolower( $artist['era'] ) );
            $score += $era_match ? 1 : 0;
            $factors++;
        }
        
        // Match medium
        if ( ! empty( $params['medium'] ) && isset( $artist['mediums'] ) && is_array( $artist['mediums'] ) ) {
            $medium_match = in_array( strtolower( $params['medium'] ), array_map( 'strtolower', $artist['mediums'] ), true );
            $score += $medium_match ? 1 : 0;
            $factors++;
        }
        
        // Match mood
        if ( ! empty( $params['mood'] ) && isset( $artist['moods'] ) && is_array( $artist['moods'] ) ) {
            $mood_match = in_array( strtolower( $params['mood'] ), array_map( 'strtolower', $artist['moods'] ), true );
            $score += $mood_match ? 1 : 0;
            $factors++;
        }
        
        // Match subject
        if ( ! empty( $params['subject'] ) && isset( $artist['subjects'] ) && is_array( $artist['subjects'] ) ) {
            foreach ( $artist['subjects'] as $subject ) {
                if ( strpos( strtolower( $params['subject'] ), strtolower( $subject ) ) !== false ) {
                    $score += 1;
                    break;
                }
            }
            $factors++;
        }
        
        // Calculate final score (normalized to 0.0-1.0)
        return $factors > 0 ? $score / $factors : 0;
    }

    /**
     * Fetch artist references from data source
     *
     * @return array Array of artist references
     */
    private function fetch_artists() {
        // In a real implementation, this might fetch from an API or database
        // For now, we'll return a static array of artists
        
        return array(
            array(
                'id' => 'vincent-van-gogh',
                'name' => 'Vincent van Gogh',
                'bio' => 'Dutch post-impressionist painter who posthumously became one of the most famous and influential figures in Western art history.',
                'era' => '19th Century',
                'styles' => array( 'Post-Impressionism', 'Expressionism' ),
                'mediums' => array( 'Oil Paint', 'Watercolor', 'Pencil Drawing' ),
                'moods' => array( 'emotional', 'vibrant', 'intense', 'melancholic' ),
                'subjects' => array( 'landscape', 'portrait', 'still life', 'nature', 'self-portrait' ),
                'keywords' => array( 'starry night', 'sunflowers', 'impasto', 'bold brushwork', 'vibrant colors', 'emotional' ),
                'thumbnail' => 'https://example.com/thumbnails/van-gogh.jpg',
                'years_active' => '1881-1890',
            ),
            array(
                'id' => 'claude-monet',
                'name' => 'Claude Monet',
                'bio' => 'French painter and founder of impressionist painting who is seen as a key precursor to modernism, especially in his attempts to paint nature as he perceived it.',
                'era' => '19th Century',
                'styles' => array( 'Impressionism' ),
                'mediums' => array( 'Oil Paint' ),
                'moods' => array( 'serene', 'peaceful', 'atmospheric', 'light' ),
                'subjects' => array( 'landscape', 'nature', 'water', 'garden', 'light effects' ),
                'keywords' => array( 'water lilies', 'haystacks', 'light', 'atmosphere', 'gardens', 'cathedrals', 'outdoors' ),
                'thumbnail' => 'https://example.com/thumbnails/monet.jpg',
                'years_active' => '1860-1926',
            ),
            array(
                'id' => 'pablo-picasso',
                'name' => 'Pablo Picasso',
                'bio' => 'Spanish painter, sculptor, printmaker, ceramicist and theatre designer who spent most of his adult life in France. One of the most influential artists of the 20th century.',
                'era' => '20th Century',
                'styles' => array( 'Cubism', 'Surrealism', 'Expressionism', 'Neoclassicism' ),
                'mediums' => array( 'Oil Paint', 'Sculpture', 'Ceramics', 'Printmaking' ),
                'moods' => array( 'bold', 'innovative', 'complex', 'intellectual' ),
                'subjects' => array( 'portrait', 'figure', 'still life', 'mythology', 'war' ),
                'keywords' => array( 'cubism', 'blue period', 'rose period', 'guernica', 'les demoiselles d\'avignon', 'multiple perspectives' ),
                'thumbnail' => 'https://example.com/thumbnails/picasso.jpg',
                'years_active' => '1894-1973',
            ),
            array(
                'id' => 'salvador-dali',
                'name' => 'Salvador Dalí',
                'bio' => 'Spanish surrealist artist renowned for his technical skill, precise draftsmanship, and the striking and bizarre images in his work.',
                'era' => '20th Century',
                'styles' => array( 'Surrealism', 'Cubism', 'Dadaism' ),
                'mediums' => array( 'Oil Paint', 'Sculpture', 'Film', 'Photography' ),
                'moods' => array( 'dreamlike', 'bizarre', 'unsettling', 'fantastical' ),
                'subjects' => array( 'dream', 'unconscious', 'symbolism', 'religion', 'science' ),
                'keywords' => array( 'melting clocks', 'persistence of memory', 'dream', 'surreal', 'paranoid-critical', 'unconscious' ),
                'thumbnail' => 'https://example.com/thumbnails/dali.jpg',
                'years_active' => '1929-1983',
            ),
            array(
                'id' => 'frida-kahlo',
                'name' => 'Frida Kahlo',
                'bio' => 'Mexican painter known for her many portraits, self-portraits, and works inspired by the nature and artifacts of Mexico.',
                'era' => '20th Century',
                'styles' => array( 'Surrealism', 'Magical Realism', 'Folk Art' ),
                'mediums' => array( 'Oil Paint' ),
                'moods' => array( 'emotional', 'painful', 'intimate', 'symbolic' ),
                'subjects' => array( 'self-portrait', 'identity', 'human body', 'nature', 'mexican culture' ),
                'keywords' => array( 'self-portrait', 'pain', 'identity', 'mexican', 'symbolism', 'autobiography' ),
                'thumbnail' => 'https://example.com/thumbnails/kahlo.jpg',
                'years_active' => '1925-1954',
            ),
            array(
                'id' => 'leonardo-da-vinci',
                'name' => 'Leonardo da Vinci',
                'bio' => 'Italian polymath of the Renaissance whose areas of interest included invention, drawing, painting, sculpture, architecture, science, music, mathematics, engineering, literature, anatomy, geology, astronomy, botany, paleontology, and cartography.',
                'era' => 'Renaissance',
                'styles' => array( 'Renaissance', 'High Renaissance' ),
                'mediums' => array( 'Oil Paint', 'Fresco', 'Drawing' ),
                'moods' => array( 'harmonious', 'balanced', 'detailed', 'naturalistic' ),
                'subjects' => array( 'portrait', 'religious', 'scientific', 'anatomical', 'nature' ),
                'keywords' => array( 'mona lisa', 'last supper', 'sfumato', 'anatomy', 'proportion', 'perspective', 'vitruvian man' ),
                'thumbnail' => 'https://example.com/thumbnails/da-vinci.jpg',
                'years_active' => '1467-1519',
            ),
            array(
                'id' => 'rembrandt',
                'name' => 'Rembrandt van Rijn',
                'bio' => 'Dutch draughtsman, painter, and printmaker. An innovative and prolific master in three media, he is generally considered one of the greatest visual artists in the history of art.',
                'era' => 'Baroque',
                'styles' => array( 'Baroque', 'Dutch Golden Age' ),
                'mediums' => array( 'Oil Paint', 'Etching', 'Drawing' ),
                'moods' => array( 'dramatic', 'introspective', 'emotional', 'contemplative' ),
                'subjects' => array( 'portrait', 'self-portrait', 'religious', 'historical', 'mythology' ),
                'keywords' => array( 'chiaroscuro', 'night watch', 'self-portrait', 'light', 'shadow', 'humanity', 'emotion' ),
                'thumbnail' => 'https://example.com/thumbnails/rembrandt.jpg',
                'years_active' => '1626-1669',
            ),
            array(
                'id' => 'georgia-okeeffe',
                'name' => 'Georgia O\'Keeffe',
                'bio' => 'American artist known for her paintings of enlarged flowers, New York skyscrapers, and New Mexico landscapes.',
                'era' => '20th Century',
                'styles' => array( 'Modernism', 'Precisionism', 'American Modernism' ),
                'mediums' => array( 'Oil Paint', 'Watercolor', 'Charcoal' ),
                'moods' => array( 'bold', 'sensual', 'intimate', 'expansive' ),
                'subjects' => array( 'flower', 'landscape', 'nature', 'skull', 'architecture' ),
                'keywords' => array( 'flowers', 'desert', 'new mexico', 'abstraction', 'nature', 'feminine', 'close-up' ),
                'thumbnail' => 'https://example.com/thumbnails/okeeffe.jpg',
                'years_active' => '1915-1986',
            ),
            array(
                'id' => 'andy-warhol',
                'name' => 'Andy Warhol',
                'bio' => 'American artist, film director, and producer who was a leading figure in the visual art movement known as pop art.',
                'era' => '20th Century',
                'styles' => array( 'Pop Art', 'Avant-garde' ),
                'mediums' => array( 'Silkscreen', 'Photography', 'Film', 'Painting' ),
                'moods' => array( 'bold', 'ironic', 'commercial', 'repetitive' ),
                'subjects' => array( 'celebrity', 'consumer product', 'advertisement', 'popular culture', 'self-portrait' ),
                'keywords' => array( 'campbell soup', 'marilyn monroe', 'silkscreen', 'factory', 'mass production', 'celebrity', 'commercial' ),
                'thumbnail' => 'https://example.com/thumbnails/warhol.jpg',
                'years_active' => '1954-1987',
            ),
            array(
                'id' => 'wassily-kandinsky',
                'name' => 'Wassily Kandinsky',
                'bio' => 'Russian painter and art theorist, generally credited as the pioneer of abstract art.',
                'era' => '20th Century',
                'styles' => array( 'Abstract', 'Expressionism', 'Bauhaus' ),
                'mediums' => array( 'Oil Paint', 'Watercolor' ),
                'moods' => array( 'spiritual', 'musical', 'emotional', 'dynamic' ),
                'subjects' => array( 'abstract', 'music', 'spirituality', 'emotion', 'color theory' ),
                'keywords' => array( 'abstract', 'spiritual', 'synesthesia', 'music', 'color theory', 'non-objective', 'composition' ),
                'thumbnail' => 'https://example.com/thumbnails/kandinsky.jpg',
                'years_active' => '1896-1944',
            ),
            array(
                'id' => 'yayoi-kusama',
                'name' => 'Yayoi Kusama',
                'bio' => 'Japanese contemporary artist who works primarily in sculpture and installation, but is also active in painting, performance, film, fashion, poetry, fiction, and other arts.',
                'era' => 'Contemporary',
                'styles' => array( 'Pop Art', 'Minimalism', 'Feminist Art', 'Installation Art' ),
                'mediums' => array( 'Installation', 'Sculpture', 'Painting', 'Performance' ),
                'moods' => array( 'obsessive', 'immersive', 'repetitive', 'vibrant' ),
                'subjects' => array( 'infinity', 'repetition', 'pattern', 'dot', 'pumpkin', 'phallus' ),
                'keywords' => array( 'polka dots', 'infinity rooms', 'pumpkins', 'nets', 'repetition', 'obsession', 'immersive' ),
                'thumbnail' => 'https://example.com/thumbnails/kusama.jpg',
                'years_active' => '1952-present',
            ),
            array(
                'id' => 'banksy',
                'name' => 'Banksy',
                'bio' => 'Anonymous England-based street artist, political activist, and film director whose satirical street art and subversive epigrams combine dark humour with graffiti.',
                'era' => 'Contemporary',
                'styles' => array( 'Street Art', 'Graffiti', 'Political Art' ),
                'mediums' => array( 'Stencil', 'Spray Paint', 'Installation' ),
                'moods' => array( 'satirical', 'political', 'provocative', 'humorous' ),
                'subjects' => array( 'politics', 'social commentary', 'anti-war', 'anti-capitalism', 'anti-establishment' ),
                'keywords' => array( 'stencil', 'political', 'satire', 'anonymous', 'street art', 'graffiti', 'social commentary' ),
                'thumbnail' => 'https://example.com/thumbnails/banksy.jpg',
                'years_active' => '1990s-present',
            ),
        );
    }
} 