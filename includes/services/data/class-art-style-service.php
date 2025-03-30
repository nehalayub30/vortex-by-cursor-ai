<?php
/**
 * Art Style Service
 *
 * @package VortexAiAgents
 * @subpackage Services\Data
 */

namespace VortexAiAgents\Services\Data;

use VortexAiAgents\Services\Cache_Service;

/**
 * Service for retrieving and managing art style data
 */
class Art_Style_Service {
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
        $this->cache_service = new Cache_Service( 'art_styles', 24 * HOUR_IN_SECONDS );
    }

    /**
     * Get all available art styles
     *
     * @return array Array of art styles with metadata
     */
    public function get_all_styles() {
        $cached_styles = $this->cache_service->get( 'all_styles' );
        
        if ( $cached_styles ) {
            return $cached_styles;
        }
        
        $styles = $this->fetch_styles();
        $this->cache_service->set( 'all_styles', $styles );
        
        return $styles;
    }

    /**
     * Get style by ID
     *
     * @param string $style_id Style identifier.
     * @return array|false Style data or false if not found
     */
    public function get_style( $style_id ) {
        $styles = $this->get_all_styles();
        
        foreach ( $styles as $style ) {
            if ( $style['id'] === $style_id ) {
                return $style;
            }
        }
        
        return false;
    }

    /**
     * Get styles by category
     *
     * @param string $category Category name.
     * @return array Filtered styles
     */
    public function get_styles_by_category( $category ) {
        $styles = $this->get_all_styles();
        $filtered = array();
        
        foreach ( $styles as $style ) {
            if ( isset( $style['category'] ) && $style['category'] === $category ) {
                $filtered[] = $style;
            }
        }
        
        return $filtered;
    }

    /**
     * Get style categories
     *
     * @return array List of unique categories
     */
    public function get_categories() {
        $styles = $this->get_all_styles();
        $categories = array();
        
        foreach ( $styles as $style ) {
            if ( isset( $style['category'] ) && ! in_array( $style['category'], $categories, true ) ) {
                $categories[] = $style['category'];
            }
        }
        
        return $categories;
    }

    /**
     * Search styles by keyword
     *
     * @param string $keyword Search keyword.
     * @return array Matching styles
     */
    public function search_styles( $keyword ) {
        $styles = $this->get_all_styles();
        $results = array();
        $keyword = strtolower( $keyword );
        
        foreach ( $styles as $style ) {
            // Search in name, description, and keywords
            if ( 
                strpos( strtolower( $style['name'] ), $keyword ) !== false ||
                ( isset( $style['description'] ) && strpos( strtolower( $style['description'] ), $keyword ) !== false ) ||
                ( isset( $style['keywords'] ) && $this->keywords_match( $style['keywords'], $keyword ) )
            ) {
                $results[] = $style;
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
     * Get style recommendations based on input parameters
     *
     * @param array $params Parameters for recommendation.
     * @return array Recommended styles
     */
    public function get_style_recommendations( $params ) {
        $styles = $this->get_all_styles();
        $scored_styles = array();
        
        // Default parameters
        $default_params = array(
            'mood' => '',
            'era' => '',
            'subject' => '',
            'complexity' => 0.5, // 0.0 to 1.0
            'abstraction' => 0.5, // 0.0 to 1.0
            'color_intensity' => 0.5, // 0.0 to 1.0
        );
        
        $params = wp_parse_args( $params, $default_params );
        
        foreach ( $styles as $style ) {
            $score = $this->calculate_style_match_score( $style, $params );
            
            if ( $score > 0 ) {
                $scored_styles[] = array(
                    'style' => $style,
                    'score' => $score,
                );
            }
        }
        
        // Sort by score (highest first)
        usort( $scored_styles, function( $a, $b ) {
            return $b['score'] <=> $a['score'];
        } );
        
        // Return top styles with scores
        $recommendations = array();
        foreach ( $scored_styles as $item ) {
            $recommendations[] = array(
                'id' => $item['style']['id'],
                'name' => $item['style']['name'],
                'description' => isset( $item['style']['description'] ) ? $item['style']['description'] : '',
                'match_score' => $item['score'],
                'thumbnail' => isset( $item['style']['thumbnail'] ) ? $item['style']['thumbnail'] : '',
            );
        }
        
        return array_slice( $recommendations, 0, 5 ); // Return top 5
    }

    /**
     * Calculate match score between style and parameters
     *
     * @param array $style Style data.
     * @param array $params Parameters to match against.
     * @return float Match score (0.0 to 1.0)
     */
    private function calculate_style_match_score( $style, $params ) {
        $score = 0;
        $factors = 0;
        
        // Match mood
        if ( ! empty( $params['mood'] ) && isset( $style['moods'] ) && is_array( $style['moods'] ) ) {
            $mood_match = in_array( strtolower( $params['mood'] ), array_map( 'strtolower', $style['moods'] ), true );
            $score += $mood_match ? 1 : 0;
            $factors++;
        }
        
        // Match era
        if ( ! empty( $params['era'] ) && isset( $style['era'] ) ) {
            $era_match = ( strtolower( $params['era'] ) === strtolower( $style['era'] ) );
            $score += $era_match ? 1 : 0;
            $factors++;
        }
        
        // Match subject compatibility
        if ( ! empty( $params['subject'] ) && isset( $style['subject_compatibility'] ) && is_array( $style['subject_compatibility'] ) ) {
            foreach ( $style['subject_compatibility'] as $subject_type ) {
                if ( strpos( strtolower( $params['subject'] ), strtolower( $subject_type ) ) !== false ) {
                    $score += 1;
                    break;
                }
            }
            $factors++;
        }
        
        // Match complexity
        if ( isset( $style['complexity'] ) ) {
            $complexity_diff = 1 - abs( $params['complexity'] - $style['complexity'] );
            $score += $complexity_diff;
            $factors++;
        }
        
        // Match abstraction
        if ( isset( $style['abstraction'] ) ) {
            $abstraction_diff = 1 - abs( $params['abstraction'] - $style['abstraction'] );
            $score += $abstraction_diff;
            $factors++;
        }
        
        // Match color intensity
        if ( isset( $style['color_intensity'] ) ) {
            $color_diff = 1 - abs( $params['color_intensity'] - $style['color_intensity'] );
            $score += $color_diff;
            $factors++;
        }
        
        // Calculate final score (normalized to 0.0-1.0)
        return $factors > 0 ? $score / $factors : 0;
    }

    /**
     * Fetch art styles from data source
     *
     * @return array Array of art styles
     */
    private function fetch_styles() {
        // In a real implementation, this might fetch from an API or database
        // For now, we'll return a static array of styles
        
        return array(
            array(
                'id' => 'impressionism',
                'name' => 'Impressionism',
                'description' => 'A 19th-century art movement characterized by small, thin brush strokes, emphasis on light, and ordinary subject matter.',
                'era' => '19th Century',
                'category' => 'Modern Art',
                'complexity' => 0.6,
                'abstraction' => 0.4,
                'color_intensity' => 0.7,
                'moods' => array( 'serene', 'peaceful', 'vibrant', 'light' ),
                'subject_compatibility' => array( 'landscape', 'nature', 'urban scene', 'portrait', 'still life' ),
                'keywords' => array( 'light', 'brushwork', 'outdoors', 'atmosphere', 'monet', 'renoir' ),
                'thumbnail' => 'https://example.com/thumbnails/impressionism.jpg',
            ),
            array(
                'id' => 'abstract-expressionism',
                'name' => 'Abstract Expressionism',
                'description' => 'Post-World War II art movement characterized by spontaneous creation, emotional intensity, and freedom of expression.',
                'era' => '20th Century',
                'category' => 'Modern Art',
                'complexity' => 0.7,
                'abstraction' => 0.9,
                'color_intensity' => 0.8,
                'moods' => array( 'energetic', 'emotional', 'chaotic', 'intense' ),
                'subject_compatibility' => array( 'abstract', 'emotion', 'concept' ),
                'keywords' => array( 'spontaneous', 'gestural', 'action painting', 'pollock', 'de kooning', 'rothko' ),
                'thumbnail' => 'https://example.com/thumbnails/abstract-expressionism.jpg',
            ),
            array(
                'id' => 'cubism',
                'name' => 'Cubism',
                'description' => 'Early 20th-century avant-garde art movement that revolutionized European painting by depicting subjects from multiple viewpoints.',
                'era' => '20th Century',
                'category' => 'Modern Art',
                'complexity' => 0.8,
                'abstraction' => 0.7,
                'color_intensity' => 0.5,
                'moods' => array( 'intellectual', 'analytical', 'complex' ),
                'subject_compatibility' => array( 'portrait', 'still life', 'figure', 'urban scene' ),
                'keywords' => array( 'geometric', 'multiple perspectives', 'picasso', 'braque', 'fragmented' ),
                'thumbnail' => 'https://example.com/thumbnails/cubism.jpg',
            ),
            array(
                'id' => 'surrealism',
                'name' => 'Surrealism',
                'description' => 'Movement that juxtaposes uncommon imagery to activate the unconscious mind and unlock creativity and imagination.',
                'era' => '20th Century',
                'category' => 'Modern Art',
                'complexity' => 0.9,
                'abstraction' => 0.6,
                'color_intensity' => 0.7,
                'moods' => array( 'dreamlike', 'mysterious', 'unsettling', 'fantastical' ),
                'subject_compatibility' => array( 'dream', 'fantasy', 'concept', 'portrait', 'landscape' ),
                'keywords' => array( 'dream', 'unconscious', 'dali', 'magritte', 'bizarre', 'fantasy' ),
                'thumbnail' => 'https://example.com/thumbnails/surrealism.jpg',
            ),
            array(
                'id' => 'pop-art',
                'name' => 'Pop Art',
                'description' => 'Art movement that emerged in the 1950s that challenges traditions by including imagery from popular culture and mass media.',
                'era' => '20th Century',
                'category' => 'Modern Art',
                'complexity' => 0.5,
                'abstraction' => 0.3,
                'color_intensity' => 0.9,
                'moods' => array( 'bold', 'ironic', 'playful', 'commercial' ),
                'subject_compatibility' => array( 'consumer product', 'celebrity', 'comic', 'advertisement', 'popular culture' ),
                'keywords' => array( 'commercial', 'warhol', 'lichtenstein', 'bright colors', 'mass production', 'popular' ),
                'thumbnail' => 'https://example.com/thumbnails/pop-art.jpg',
            ),
            array(
                'id' => 'minimalism',
                'name' => 'Minimalism',
                'description' => 'Art movement that uses simple, geometric forms and industrial materials to create works of extreme simplicity.',
                'era' => '20th Century',
                'category' => 'Modern Art',
                'complexity' => 0.2,
                'abstraction' => 0.8,
                'color_intensity' => 0.3,
                'moods' => array( 'calm', 'ordered', 'simple', 'meditative' ),
                'subject_compatibility' => array( 'abstract', 'geometric', 'concept' ),
                'keywords' => array( 'simple', 'geometric', 'monochrome', 'judd', 'flavin', 'clean' ),
                'thumbnail' => 'https://example.com/thumbnails/minimalism.jpg',
            ),
            array(
                'id' => 'renaissance',
                'name' => 'Renaissance',
                'description' => 'Art movement characterized by realistic depiction, perspective, and classical influences from 14th to 17th century Europe.',
                'era' => 'Renaissance',
                'category' => 'Classical Art',
                'complexity' => 0.7,
                'abstraction' => 0.1,
                'color_intensity' => 0.6,
                'moods' => array( 'harmonious', 'balanced', 'dignified', 'classical' ),
                'subject_compatibility' => array( 'portrait', 'religious', 'mythological', 'historical', 'figure' ),
                'keywords' => array( 'perspective', 'realistic', 'da vinci', 'michelangelo', 'classical', 'proportion' ),
                'thumbnail' => 'https://example.com/thumbnails/renaissance.jpg',
            ),
            array(
                'id' => 'baroque',
                'name' => 'Baroque',
                'description' => 'Highly ornate and dramatic style that emphasizes grandeur, drama, and movement from the 17th and 18th centuries.',
                'era' => 'Baroque',
                'category' => 'Classical Art',
                'complexity' => 0.8,
                'abstraction' => 0.2,
                'color_intensity' => 0.7,
                'moods' => array( 'dramatic', 'emotional', 'grand', 'dynamic' ),
                'subject_compatibility' => array( 'religious', 'mythological', 'portrait', 'historical', 'still life' ),
                'keywords' => array( 'dramatic', 'chiaroscuro', 'caravaggio', 'rubens', 'rembrandt', 'movement' ),
                'thumbnail' => 'https://example.com/thumbnails/baroque.jpg',
            ),
            array(
                'id' => 'romanticism',
                'name' => 'Romanticism',
                'description' => 'Artistic movement that emphasized emotion, individualism, and glorification of nature and the past, especially the medieval.',
                'era' => '19th Century',
                'category' => 'Classical Art',
                'complexity' => 0.6,
                'abstraction' => 0.3,
                'color_intensity' => 0.7,
                'moods' => array( 'emotional', 'passionate', 'dramatic', 'nostalgic' ),
                'subject_compatibility' => array( 'landscape', 'historical', 'literary', 'mythological', 'nature' ),
                'keywords' => array( 'emotion', 'nature', 'delacroix', 'turner', 'friedrich', 'sublime' ),
                'thumbnail' => 'https://example.com/thumbnails/romanticism.jpg',
            ),
            array(
                'id' => 'digital-art',
                'name' => 'Digital Art',
                'description' => 'Art created or modified using digital technology, encompassing a wide range of styles and techniques.',
                'era' => 'Contemporary',
                'category' => 'Digital Art',
                'complexity' => 0.7,
                'abstraction' => 0.5,
                'color_intensity' => 0.8,
                'moods' => array( 'modern', 'technological', 'innovative', 'diverse' ),
                'subject_compatibility' => array( 'any', 'sci-fi', 'fantasy', 'concept', 'abstract' ),
                'keywords' => array( 'digital', 'computer', 'photoshop', 'cgi', 'modern', 'tech' ),
                'thumbnail' => 'https://example.com/thumbnails/digital-art.jpg',
            ),
            array(
                'id' => 'pixel-art',
                'name' => 'Pixel Art',
                'description' => 'Digital art form where images are created at the pixel level, often reminiscent of early computer and video game graphics.',
                'era' => 'Contemporary',
                'category' => 'Digital Art',
                'complexity' => 0.5,
                'abstraction' => 0.4,
                'color_intensity' => 0.7,
                'moods' => array( 'nostalgic', 'playful', 'retro', 'gaming' ),
                'subject_compatibility' => array( 'character', 'game', 'scene', 'icon', 'landscape' ),
                'keywords' => array( 'pixel', 'retro', 'game', '8-bit', '16-bit', 'blocky' ),
                'thumbnail' => 'https://example.com/thumbnails/pixel-art.jpg',
            ),
            array(
                'id' => 'vaporwave',
                'name' => 'Vaporwave',
                'description' => 'Internet-based aesthetic that combines elements of glitch art, early web design, and 1980s-90s consumer culture.',
                'era' => 'Contemporary',
                'category' => 'Digital Art',
                'complexity' => 0.6,
                'abstraction' => 0.5,
                'color_intensity' => 0.9,
                'moods' => array( 'nostalgic', 'surreal', 'ironic', 'retro' ),
                'subject_compatibility' => array( 'retro technology', 'consumer culture', 'glitch', 'landscape', 'portrait' ),
                'keywords' => array( 'retro', '80s', '90s', 'glitch', 'pastel', 'cyberpunk', 'nostalgic' ),
                'thumbnail' => 'https://example.com/thumbnails/vaporwave.jpg',
            ),
        );
    }
} 