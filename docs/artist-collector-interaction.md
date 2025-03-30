# VORTEX AI AGENTS: Artist-Collector Interaction Guide

This document details the artist-collector interaction features of the VORTEX AI AGENTS plugin, including the swapping functionality between artist and collector views, and provides a comprehensive demo scenario showcasing the complete user journey.

## Table of Contents
- [Artist-Collector Swapping](#artist-collector-swapping)
- [Swiping Between Roles](#swiping-between-roles)
- [Transaction Fees](#transaction-fees)
- [Gamification Metrics & Rankings](#gamification-metrics--rankings)
- [Demo Scenario: New User Journey](#demo-scenario-new-user-journey)
- [Technical Implementation](#technical-implementation)
- [Best Practices](#best-practices)

## Artist-Collector Swapping

The VORTEX AI AGENTS plugin allows users to seamlessly switch between artist and collector roles, providing a unified experience for users who operate in both capacities within the art ecosystem.

### How Role Swapping Works

1. **Dual Profile System**: Users can maintain both artist and collector profiles under a single account
2. **Context-Aware Interface**: The interface adapts based on the active role
3. **Persistent Data**: Data and preferences for each role are maintained separately
4. **Unified Notifications**: Notifications from both roles are accessible regardless of current view

### Accessing Role Swap

The role swap feature can be accessed through:

1. **Header Menu**: Click the profile icon in the header, then select "Switch to Artist/Collector"
2. **Dashboard Widget**: Use the role toggle switch in the dashboard sidebar
3. **Quick Swap Gesture**: Use the two-finger horizontal swipe gesture on touch devices
4. **Keyboard Shortcut**: Press Alt+R to toggle between roles

### Role-Specific Features

| Feature | Artist View | Collector View |
|---------|------------|----------------|
| Dashboard | Creation metrics, style analysis | Collection value, acquisition opportunities |
| Portfolio | Artwork management, creation tools | Collection management, acquisition history |
| Analytics | Market fit, price optimization | Investment performance, market trends |
| Recommendations | Style development, exhibition opportunities | Acquisition suggestions, emerging artists |
| HURAII Access | Creation-focused generation | Collection-focused curation |

## Swiping Between Roles

The swiping functionality provides an intuitive way to navigate between artist and collector contexts.

### Gesture Controls

1. **Horizontal Swipe**: Swipe left/right to switch between artist and collector roles
2. **Vertical Swipe**: Swipe up/down to navigate between different sections within the current role
3. **Pinch Gesture**: Pinch to zoom out to an overview of both roles
4. **Spread Gesture**: Spread to zoom into detailed view of current section

### Visual Indicators

The interface provides clear visual cues for role context:

1. **Color Coding**: Artist view uses a blue color scheme, collector view uses a green color scheme
2. **Role Badge**: Current role is displayed in the header
3. **Transition Animation**: Smooth animation indicates role transition
4. **Context Breadcrumbs**: Navigation path shows current role context

### Customizing Swipe Sensitivity

Users can customize swipe sensitivity and behavior:

1. Navigate to: VORTEX AI > User Preferences
2. Select the "Interface" tab
3. Adjust "Swipe Sensitivity" slider
4. Toggle "Enable/Disable Gesture Controls"

## Transaction Fees

All transactions within the VORTEX AI AGENTS platform are conducted using TOLA (Token of Love and Appreciation), providing a seamless and unified payment system across the entire ecosystem.

### Fee Structure

| Transaction Type | Fee Amount | Payment Method | Notes |
|------------------|------------|----------------|-------|
| Swapping Between Artists | $3 | TOLA | Applied when transferring artwork rights between artists |
| Artwork Buy/Sell | $80 | TOLA | Can be configured as split payment or one-sided payment |
| NFT Minting | Variable | TOLA + Gas Fees | Depends on blockchain network congestion |
| HURAII Generation | 25 TOLA points | TOLA | Per generation session |

### Payment Options

1. **Split Payment**: Transaction fee can be divided between buyer and seller
2. **One-Sided Payment**: Either buyer or seller can cover the entire transaction fee
3. **TOLA Discounts**: Users with higher TOLA levels receive discounted transaction fees
4. **Bulk Transaction**: Reduced fees when conducting multiple transactions within 24 hours

### Fee Allocation

- 40% of transaction fees contribute to platform maintenance and development
- 30% of transaction fees are converted to TOLA rewards for active community members
- 20% of transaction fees support the AI training and improvement fund
- 10% of transaction fees go to community-selected charitable causes

### Transaction Acceleration

Users can allocate additional TOLA points to prioritize their transactions:
- Standard Processing: Included in base fee
- Priority Processing: +10 TOLA points
- Express Processing: +25 TOLA points

## Gamification Metrics & Daily Rankings

The VORTEX AI AGENTS platform incorporates a comprehensive gamification system that rewards and motivates users through metrics tracking, daily rankings, and achievement systems. This section details how these systems function and interact.

### Core Metrics

The platform tracks a diverse set of metrics for both artists and collectors that form the foundation of the gamification system:

#### Artist Metrics
- **Creation Frequency**: Measures volume of artwork production (5-25 points per work, max 500/week)
- **Technical Quality**: AI-assessed technical execution (up to 50 points per work)
- **Stylistic Consistency**: Evaluates coherence of artistic style across portfolio (up to 40 points)
- **Originality**: Evaluates uniqueness compared to platform database (up to 50 points per work)
- **Description Quality**: Assesses thoroughness of artwork metadata (up to 30 points per work)
- **Media Diversity**: Range of artistic mediums explored (10 points per medium)
- **Subject Exploration**: Diversity of themes and subjects (5 points per subject)
- **Community Engagement**: Interaction with other artists (3 points per meaningful interaction)

#### Collector Metrics
- **Collection Volume**: Number of works collected (10-30 points per acquisition)
- **Collection Coherence**: Thematic/stylistic consistency (up to 100 points)
- **Collection Diversity**: Range within a cohesive framework (up to 80 points)
- **Trend Anticipation**: Early support for trending artists (up to 200 points per acquisition)
- **Documentation**: Collection cataloging quality (up to 50 points)
- **Market Acumen**: Value appreciation of acquired works (unlimited)
- **TOLA Distribution**: Support shown to creators (1.2× multiplier on TOLA awarded)
- **Education Contribution**: Knowledge sharing activities (up to 100 points per contribution)

#### Community Metrics
- **Helpfulness**: Assistance provided to others (up to 200 points per week)
- **Collaboration**: Participation in joint projects (25-100 points based on scope)
- **Network Size**: Active connections maintained (2 points per connection)
- **Feedback Quality**: Value of feedback provided (up to 50 points per feedback)
- **Resource Sharing**: Useful resources contributed (10 points per resource)
- **Event Participation**: Activity in platform events (15 points per event)

### Daily Rankings

The platform features daily leaderboards that reset every 24 hours at midnight UTC, providing short-term goals and promoting consistent engagement.

#### Ranking Categories

1. **Creator Rankings**
   - Most Creative: Based on originality, style distinctiveness, and novelty
   - Most Prolific: Creation count with quality modifier
   - Most Appreciated: TOLA received, weighted comments, and saves
   - Rising Star: Growth rate in profile views, followers, and appreciation

2. **Collector Rankings**
   - Top Curator: Collection coherence and curator influence
   - Most Generous: TOLA awarded and feedback provided
   - Trend Spotter: Support for artists before popularity increases
   - Collection Growth: Collection value appreciation and acquisition quality

3. **Community Rankings**
   - Most Helpful: Help metrics and user gratitude indicators
   - Most Connected: Network size and engagement depth
   - Most Influential: Content reach, engagement, and conversation generation
   - Most Engaged: Platform time, meaningful interactions, and activity breadth

#### Ranking Rewards

Daily ranking positions earn immediate benefits:

| Ranking Position | TOLA Reward | Badge | Visibility Boost |
|------------------|-------------|-------|-----------------|
| 1st Place | 100 TOLA | Daily Gold | 24-hour spotlight |
| 2nd-5th Place | 50 TOLA | Daily Silver | Enhanced discovery |
| 6th-20th Place | 25 TOLA | Daily Bronze | Modest boost |
| 21st-100th Place | 10 TOLA | Recognition | Minor boost |

Weekly cumulative ranking performance earns additional rewards:
- Top Performer: 500 TOLA + Featured profile for 7 days
- Top 5 Consistent: 250 TOLA + 7-day visibility boost
- Top 20 Consistent: 100 TOLA + Special achievement badge
- Top 100 Consistent: 50 TOLA + Community newsletter feature

#### Streak System

Consecutive days in rankings receive compounding benefits:
- 3-6 days: 1.2× TOLA rewards + Special streak indicator
- 7-13 days: 1.5× TOLA rewards + Bonus badges
- 14-20 days: 2.0× TOLA rewards + Early access to new features
- 21+ days: 3.0× TOLA rewards + VIP status indicators

### Visualization Tools

Users can track their metrics and rankings through:

1. **Radar Charts**: Displaying strengths across 8 core metrics
2. **Performance Graphs**: Line graphs showing metric development over time
3. **Ranking History**: Position tracking for each ranking category
4. **Activity Impact**: Correlation between activities and metric changes
5. **Percentile Indicators**: Relative standing within community
6. **Progress Bars**: Visual indicators for achievement completion

### Progression System

The platform incorporates a tiered progression system that recognizes cumulative achievement:

#### Artist Progression Path

| Tier | Name | Requirements | Key Benefits |
|------|------|-------------|------------|
| 1 | Novice | New account | Basic tools access |
| 2 | Emerging | 500 TOLA + 10 quality works | +10% visibility |
| 3 | Established | 2,000 TOLA + 25 quality works | Priority HURAII access |
| 4 | Professional | 5,000 TOLA + 50 quality works | Featured status |
| 5 | Master | 10,000 TOLA + 100 quality works | Exclusive features |
| 6 | Luminary | 25,000 TOLA + recognition | Platform ambassadorship |

#### Collector Progression Path

| Tier | Name | Requirements | Key Benefits |
|------|------|-------------|------------|
| 1 | Browser | New account | Basic features |
| 2 | Enthusiast | 500 TOLA + 5 acquisitions | Early notifications |
| 3 | Connoisseur | 2,000 TOLA + 15 acquisitions | Market insights |
| 4 | Patron | 5,000 TOLA + 30 acquisitions | VIP invitations |
| 5 | Luminary | 10,000 TOLA + 50 acquisitions | Exclusive deals |
| 6 | Benefactor | 25,000 TOLA + significant impact | Legacy program |

### Seasonal Competitions

The platform organizes quarterly seasonal competitions with:
- 12-week duration with unifying artistic themes
- Weekly and seasonal objectives aligned with theme
- Themed exhibitions and competitions
- End-of-season recognition ceremony
- Exclusive seasonal rewards (limited editions, profile enhancements, TOLA bonuses)

### Technical Implementation

The metrics and ranking system is implemented through:

```php
/**
 * Manages daily rankings calculation and rewards
 */
class Vortex_Gamification_Service {
    /**
     * Calculate daily rankings for all categories
     */
    public function calculate_daily_rankings() {
        $categories = $this->get_ranking_categories();
        
        foreach ($categories as $category) {
            $this->calculate_category_rankings($category);
        }
        
        do_action('vortex_daily_rankings_calculated');
    }
    
    /**
     * Get user's current rankings
     */
    public function get_user_rankings($user_id) {
        global $wpdb;
        
        $today = current_time('Y-m-d');
        
        $rankings = $wpdb->get_results($wpdb->prepare(
            "SELECT category, rank, score 
             FROM wp_vortex_daily_rankings 
             WHERE user_id = %d AND date = %s",
            $user_id, $today
        ), ARRAY_A);
        
        return $rankings;
    }
    
    /**
     * Award daily ranking rewards
     */
    public function process_daily_rewards() {
        global $wpdb;
        
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        // Get top 100 in each category
        $rankings = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT user_id, category, rank 
                 FROM wp_vortex_daily_rankings 
                 WHERE date = %s AND rank <= 100 
                 ORDER BY category, rank",
                $yesterday
            ),
            ARRAY_A
        );
        
        foreach ($rankings as $ranking) {
            $tola_reward = $this->get_rank_reward($ranking['rank']);
            $badge = $this->get_rank_badge($ranking['rank']);
            
            // Check for streak multiplier
            $streak_days = $this->get_user_streak($ranking['user_id'], $ranking['category']);
            $multiplier = $this->get_streak_multiplier($streak_days);
            
            // Apply multiplier to TOLA reward
            $final_tola = round($tola_reward * $multiplier);
            
            // Award TOLA
            vortex_award_tola($ranking['user_id'], $final_tola, 'daily_ranking');
            
            // Award badge
            if ($badge) {
                vortex_award_badge($ranking['user_id'], $badge);
            }
            
            // Apply visibility boost
            if ($ranking['rank'] <= 20) {
                $boost_level = $this->get_visibility_boost_level($ranking['rank']);
                $this->apply_visibility_boost($ranking['user_id'], $boost_level);
            }
            
            // Record reward in history
            $this->record_ranking_reward(
                $ranking['user_id'],
                $ranking['category'],
                $ranking['rank'],
                $final_tola,
                $badge
            );
        }
    }
    
    /**
     * Get streak multiplier based on consecutive days
     */
    private function get_streak_multiplier($streak_days) {
        if ($streak_days >= 21) {
            return 3.0;
        } elseif ($streak_days >= 14) {
            return 2.0;
        } elseif ($streak_days >= 7) {
            return 1.5; 
        } elseif ($streak_days >= 3) {
            return 1.2;
        } else {
            return 1.0;
        }
    }
    
    /**
     * Get user's metric data for visualization
     */
    public function get_user_metric_data($user_id, $metric_keys = []) {
        // Implementation for retrieving metric data
    }
}
```

### User Experience Integration

The gamification metrics and rankings are integrated throughout the user experience:

1. **Dashboard Widgets**:
   - Metrics Summary: Compact view of key metrics with trends
   - Daily Ranking: Current positions and ranking targets
   - Achievement Progress: Next achievements and recent earnings

2. **Interactive Elements**:
   - Live Updates: Real-time position changes with notifications
   - Contextual Tips: Activity suggestions based on metrics
   - Personalized Goals: Suggested daily actions

3. **Visual Design**:
   - Color-Coded Indicators: Green for improvements, red for declines
   - Progress Visualization: Circular wheels, bars, and milestones
   - Badges and Rewards: Hierarchical display of achievements

### Best Practices

For artists to maximize their metrics and rankings:
1. Focus on quality over quantity
2. Develop a consistent, recognizable style
3. Engage meaningfully with the community
4. Complete thorough metadata for all works
5. Balance specialization with exploration

For collectors to optimize their metrics:
1. Develop cohesive collection themes
2. Document acquisitions thoroughly
3. Support emerging artists early
4. Provide thoughtful feedback to creators
5. Balance collection breadth and depth

For more detailed information on the TOLA metrics and ranking system, see the [dedicated documentation](tola-metrics-rankings.md).

## Demo Scenario: New User Journey

This comprehensive demo showcases the complete journey of a new user named "Alex" from registration to creating and selling artwork using the VORTEX AI AGENTS platform.

### Step 1: Registration and Profile Creation

1. **Registration**:
   - Alex visits the VORTEX AI AGENTS platform and clicks "Sign Up"
   - Completes registration form with email, password, and username
   - Receives welcome email with verification link
   - Verifies account and logs in

2. **Initial Profile Setup**:
   - Greeted with onboarding wizard
   - Selects primary role: "I'm primarily an artist but also collect"
   - Completes basic profile information:
     - Name: Alex Rivera
     - Location: Barcelona, Spain
     - Bio: "Contemporary digital artist exploring the intersection of technology and traditional techniques"
   - Uploads profile picture

3. **Artist Profile Details**:
   - Completes artist-specific information:
     - Artistic Focus: Digital Art, Mixed Media
     - Experience Level: Mid-Career
     - Website: alexrivera.com
     - Social Media: @alexrivera_art
   - Uploads 3 sample artworks to establish portfolio
   - Receives initial TOLA points (50) for completing profile

4. **Dashboard Introduction**:
   - Guided tour highlights key dashboard features
   - TOLA points display shows 50 points
   - Achievement section shows "Profile Pioneer" badge unlocked
   - Market Fit Analysis shows "Insufficient data - create more work"
   - Recommendation panel suggests "Generate your first AI-assisted artwork"

### Step 2: Creating Artwork with HURAII

1. **Accessing HURAII Studio**:
   - Alex clicks "Create New Artwork" from dashboard
   - Selects "HURAII AI Generation" from creation options
   - Enters HURAII Studio interface

2. **Setting Generation Parameters**:
   - Enters prompt: "Urban landscape with digital glitch aesthetic, merging Barcelona architecture with circuit patterns"
   - Selects style: "Contemporary Digital"
   - Chooses influences: "Refik Anadol, Krista Kim"
   - Sets parameters:
     - Resolution: 2048x2048
     - Complexity: High
     - Color Palette: Vibrant
     - Variations: 3

3. **Generating and Refining**:
   - Clicks "Generate" button (costs 25 TOLA points)
   - System displays generation progress with visual feedback
   - After 30 seconds, 3 variations appear
   - Alex selects favorite variation
   - Makes adjustments:
     - Increases color saturation
     - Adds more architectural elements
     - Refines glitch patterns
   - Generates final version

4. **Artwork Details and Metadata**:
   - Titles artwork: "Barcelona Circuitry"
   - Adds description: "An exploration of urban architecture as digital infrastructure, highlighting the invisible networks that connect us."
   - Tags: #DigitalArt #UrbanLandscape #Glitch #Barcelona
   - Sets categories: Digital Art, Urban, Abstract
   - Adds creation date and medium information

5. **Portfolio Addition**:
   - Saves artwork to portfolio
   - Sets visibility to "Public"
   - Receives notification: "Artwork added to portfolio and earned 75 TOLA points"
   - Dashboard updates to show new artwork and updated TOLA balance (100 points)

### Step 3: Creating NFT and Setting Royalties

1. **NFT Creation Process**:
   - From portfolio, Alex selects "Barcelona Circuitry"
   - Clicks "Create NFT" button
   - System displays NFT creation interface

2. **Blockchain Setup**:
   - Prompted to connect wallet if not already connected
   - Connects MetaMask wallet
   - Selects network: Polygon (for lower gas fees)
   - Views estimated gas fees

3. **Royalty Configuration**:
   - Views default royalty structure:
     - Platform Fee: 3% (fixed)
     - Creator Royalty: 7% (adjustable)
   - Adjusts creator royalty to 10%
   - Adds collaborator:
     - Name: Carlos Mendez (photographer who provided reference images)
     - Wallet: 0x1234...abcd
     - Royalty Split: 20% of creator royalty (2% of total sales)
   - Reviews final royalty structure:
     - Platform: 3%
     - Alex: 8%
     - Carlos: 2%

4. **Minting Process**:
   - Clicks "Mint NFT" button
   - Confirms transaction in MetaMask
   - Views progress indicator during minting
   - Receives confirmation: "NFT successfully minted!"
   - Views NFT details:
     - Contract Address: 0xabcd...1234
     - Token ID: 12345
     - Blockchain: Polygon
     - Royalty Information: Visible on-chain
     - Unique URL: https://vortex.ai/nft/12345

5. **TOLA Rewards**:
   - Earns 150 TOLA points for first NFT creation
   - Unlocks "Blockchain Pioneer" achievement
   - Dashboard updates to show NFT creation activity

### Step 4: Switching to Collector Role

1. **Role Swap**:
   - Alex swipes right on dashboard (or clicks "Switch to Collector" button)
   - Interface transitions with smooth animation
   - Color scheme changes from blue (artist) to green (collector)
   - Dashboard reconfigures to show collector-specific information

2. **Collector Dashboard Overview**:
   - Collection summary shows "0 items in collection"
   - Market opportunities panel shows trending artists
   - TOLA balance displays 250 points (shared between roles)
   - Recommendations show "Complete your collector profile"

3. **Collector Profile Setup**:
   - Completes collector-specific information:
     - Collection Focus: Digital Art, New Media
     - Acquisition Budget: €1,000 - €5,000
     - Collection Goals: Supporting emerging artists, Technology-focused art
   - Receives 50 additional TOLA points for completing collector profile

### Step 5: Exploring and Collecting Artwork

1. **Browsing Artwork**:
   - Navigates to "Discover" section
   - Views curated selection of artworks based on profile preferences
   - Filters by:
     - Medium: Digital
     - Price Range: €500 - €2,000
     - Style: Contemporary, Abstract
   - Sorts by: "Recently Added"

2. **Artwork Interaction**:
   - Discovers artwork "Digital Dreamscape" by artist Maya Chen
   - Views detailed information:
     - Creation date, medium, dimensions
     - Artist background and statement
     - Market analysis and price history
     - Royalty structure (3% platform, 10% artist)
   - Awards 5 TOLA points to artwork (appreciation)
   - Saves to "Favorites" collection

3. **Acquisition Process**:
   - Clicks "Acquire" button on "Digital Dreamscape"
   - Views acquisition options:
     - Direct Purchase: €750
     - Make Offer: Set custom price
     - Payment Methods: Crypto or Fiat
   - Selects "Direct Purchase" with crypto payment
   - Connects wallet (already connected from artist role)
   - Reviews transaction fee of $80 (paid in TOLA equivalent)
   - Selects "Split Fee" option to share cost with seller
   - Confirms transaction

4. **Transaction Processing**:
   - System processes transaction using TOLA for acceleration
   - TOLA points (25) used to prioritize transaction
   - Transaction completes in under 30 seconds
   - Ownership transfers to Alex's wallet
   - Royalties distributed automatically:
     - Platform: 3% (€22.50)
     - Artist: 10% (€75.00)
   - Transaction fee of $80 (in TOLA) processed:
     - Buyer pays $40 equivalent in TOLA
     - Seller pays $40 equivalent in TOLA

5. **Post-Acquisition**:
   - Receives digital certificate of authenticity
   - Artwork appears in "My Collection" section
   - Unlocks "First Acquisition" achievement
   - Earns 100 TOLA points for first purchase
   - Dashboard updates to show acquisition activity

### Step 6: Networking and Community Engagement

1. **Artist Discovery**:
   - System recommends following Maya Chen (creator of purchased artwork)
   - Alex follows Maya's profile
   - Explores Maya's other works and exhibition history
   - Leaves thoughtful comment on another artwork

2. **Community Participation**:
   - Joins virtual exhibition "Digital Frontiers"
   - Participates in live discussion with artists and collectors
   - Contributes to forum thread about digital art preservation
   - Awards TOLA points to insightful comments

3. **Collaboration Opportunities**:
   - Receives collaboration invitation from Maya Chen
   - Swipes left to return to Artist role
   - Reviews collaboration proposal for joint digital installation
   - Accepts collaboration and begins planning

4. **Professional Networking**:
   - Discovers gallery "New Media Space" through recommendations
   - Views gallery profile and represented artists
   - Requests introduction to gallery curator
   - Schedules virtual meeting to discuss potential exhibition

5. **Artist Swapping**:
   - Receives swap request from another artist, Javier, who wants rights to remix one of Alex's works
   - Reviews swap details:
     - Javier offers one of his artworks in exchange
     - Swap fee: $3 (paid in TOLA)
     - Rights included: Remix and derivative creation
   - Accepts swap request and selects "Split Fee" option
   - Both artists pay $1.50 in TOLA equivalent
   - System processes swap and updates rights records
   - Alex receives Javier's artwork in his collection
   - Javier receives rights to remix Alex's artwork
   - Both artists earn "Collaboration" achievement
   - Swap is recorded on the blockchain with a unique identifier

### Step 7: Ongoing Engagement and Growth

1. **Regular Creation**:
   - Creates new artwork weekly using HURAII
   - Refines style based on AI analysis and feedback
   - Builds consistent portfolio with thematic coherence
   - Tracks market response and adjusts approach

2. **Collection Development**:
   - Acquires artwork strategically based on recommendations
   - Builds collection with clear thematic focus
   - Tracks collection value and growth over time
   - Receives recognition as emerging collector

3. **TOLA Progression**:
   - Accumulates TOLA points through regular activity
   - Reaches "Established Creator" level (1000+ points)
   - Unlocks premium features and opportunities
   - Gains visibility in platform recommendations

4. **Blockchain Integration**:
   - Mints select artworks as NFTs
   - Tracks royalty income from secondary sales
   - Participates in virtual gallery exhibitions
   - Builds reputation on-chain and off-chain

## Technical Implementation

The artist-collector swapping functionality is implemented through several key components:

### 1. User Role Management

```php
// User can have multiple roles simultaneously
function vortex_set_user_roles($user_id, $roles) {
    // Store roles in user meta
    update_user_meta($user_id, 'vortex_user_roles', $roles);
    
    // Set active role
    if (!empty($roles)) {
        update_user_meta($user_id, 'vortex_active_role', $roles[0]);
    }
}

// Get user's active role
function vortex_get_active_role($user_id) {
    return get_user_meta($user_id, 'vortex_active_role', true);
}

// Switch user's active role
function vortex_switch_role($user_id, $new_role) {
    $roles = get_user_meta($user_id, 'vortex_user_roles', true);
    
    if (in_array($new_role, $roles)) {
        update_user_meta($user_id, 'vortex_active_role', $new_role);
        return true;
    }
    
    return false;
}
```

### 2. Interface Adaptation

```javascript
// Role switching handler
function handleRoleSwitch(newRole) {
    // Save current state
    saveCurrentState();
    
    // Update UI
    document.body.setAttribute('data-role', newRole);
    
    // Load role-specific components
    loadRoleComponents(newRole);
    
    // Update navigation
    updateNavigation(newRole);
    
    // Trigger role-specific data loading
    loadRoleData(newRole);
    
    // Announce role change for screen readers
    announceRoleChange(newRole);
}

// Swipe gesture detection
function initSwipeDetection() {
    const touchSurface = document.getElementById('main-content');
    let startX, startY, distX, distY;
    const threshold = 150; // Minimum distance for swipe
    
    touchSurface.addEventListener('touchstart', function(e) {
        startX = e.touches[0].clientX;
        startY = e.touches[0].clientY;
    }, false);
    
    touchSurface.addEventListener('touchend', function(e) {
        distX = e.changedTouches[0].clientX - startX;
        distY = e.changedTouches[0].clientY - startY;
        
        // Horizontal swipe detection (left/right)
        if (Math.abs(distX) >= threshold && Math.abs(distY) <= threshold * 0.5) {
            if (distX > 0) {
                // Right swipe - switch to collector
                handleRoleSwitch('collector');
            } else {
                // Left swipe - switch to artist
                handleRoleSwitch('artist');
            }
        }
        
        // Vertical swipe detection (up/down)
        if (Math.abs(distY) >= threshold && Math.abs(distX) <= threshold * 0.5) {
            if (distY > 0) {
                // Down swipe - navigate down
                navigateSection('down');
            } else {
                // Up swipe - navigate up
                navigateSection('up');
            }
        }
    }, false);
}
```

### 3. Data Context Management

```php
// Load context-specific data based on active role
function vortex_load_role_context($user_id) {
    $active_role = vortex_get_active_role($user_id);
    
    switch ($active_role) {
        case 'artist':
            return array(
                'dashboard_widgets' => vortex_get_artist_widgets($user_id),
                'navigation' => vortex_get_artist_navigation(),
                'metrics' => vortex_get_artist_metrics($user_id),
                'recommendations' => vortex_get_artist_recommendations($user_id)
            );
            
        case 'collector':
            return array(
                'dashboard_widgets' => vortex_get_collector_widgets($user_id),
                'navigation' => vortex_get_collector_navigation(),
                'metrics' => vortex_get_collector_metrics($user_id),
                'recommendations' => vortex_get_collector_recommendations($user_id)
            );
            
        default:
            return array();
    }
}
```

### 4. Transaction Fee Management

```php
/**
 * Transaction fee handling system for VORTEX AI AGENTS platform
 * All transactions utilize TOLA as the payment method
 */
class Vortex_Transaction_Service {
    // Transaction fee constants
    const ARTIST_SWAP_FEE = 3;  // $3 for swapping between artists
    const ARTWORK_TRANSACTION_FEE = 80; // $80 for buy/sell transactions
    
    // TOLA conversion rate (dynamic in production)
    private $tola_to_usd_rate = 0.1; // 1 TOLA = $0.1 USD
    
    /**
     * Process transaction between users
     *
     * @param int $sender_id User ID of sender
     * @param int $recipient_id User ID of recipient
     * @param string $transaction_type Type of transaction ('swap', 'buy_sell', 'nft_mint')
     * @param float $amount Transaction amount
     * @param array $options Additional options
     * @return bool|array Success or error information
     */
    public function process_transaction($sender_id, $recipient_id, $transaction_type, $amount, $options = []) {
        // Determine transaction fee
        $fee = $this->calculate_fee($transaction_type, $amount, $options);
        
        // Convert fee to TOLA amount
        $tola_fee = $this->convert_usd_to_tola($fee);
        
        // Determine fee payment arrangement
        $fee_arrangement = isset($options['fee_arrangement']) ? $options['fee_arrangement'] : 'sender_pays';
        
        // Apply fee based on arrangement
        switch ($fee_arrangement) {
            case 'split':
                $sender_fee = $tola_fee / 2;
                $recipient_fee = $tola_fee / 2;
                break;
            case 'recipient_pays':
                $sender_fee = 0;
                $recipient_fee = $tola_fee;
                break;
            case 'sender_pays':
            default:
                $sender_fee = $tola_fee;
                $recipient_fee = 0;
                break;
        }
        
        // Check if sender has sufficient TOLA balance
        if (!$this->has_sufficient_tola($sender_id, $sender_fee)) {
            return [
                'success' => false,
                'error' => 'insufficient_tola',
                'message' => 'Sender has insufficient TOLA balance for transaction fee'
            ];
        }
        
        // Check if recipient has sufficient TOLA balance (for split/recipient pays)
        if ($recipient_fee > 0 && !$this->has_sufficient_tola($recipient_id, $recipient_fee)) {
            return [
                'success' => false,
                'error' => 'recipient_insufficient_tola',
                'message' => 'Recipient has insufficient TOLA balance for transaction fee'
            ];
        }
        
        // Process transaction acceleration if requested
        if (isset($options['acceleration']) && $options['acceleration'] > 0) {
            $this->process_acceleration($sender_id, $options['acceleration']);
        }
        
        // Deduct fees from balances
        $this->deduct_tola($sender_id, $sender_fee);
        $this->deduct_tola($recipient_id, $recipient_fee);
        
        // Allocate fees according to distribution policy
        $this->allocate_transaction_fees($tola_fee);
        
        // Record transaction in database
        $transaction_id = $this->record_transaction($sender_id, $recipient_id, $transaction_type, $amount, $tola_fee, $options);
        
        // Return success response
        return [
            'success' => true,
            'transaction_id' => $transaction_id,
            'fee' => [
                'total' => $fee,
                'tola_amount' => $tola_fee,
                'sender_portion' => $sender_fee,
                'recipient_portion' => $recipient_fee
            ]
        ];
    }
    
    /**
     * Calculate transaction fee based on type and amount
     *
     * @param string $transaction_type Type of transaction
     * @param float $amount Transaction amount
     * @param array $options Additional options
     * @return float Fee amount in USD
     */
    private function calculate_fee($transaction_type, $amount, $options = []) {
        $user_id = isset($options['user_id']) ? $options['user_id'] : 0;
        $discount = $this->get_user_fee_discount($user_id);
        
        switch ($transaction_type) {
            case 'swap':
                $base_fee = self::ARTIST_SWAP_FEE;
                break;
            case 'buy_sell':
                $base_fee = self::ARTWORK_TRANSACTION_FEE;
                break;
            case 'nft_mint':
                // NFT minting has variable fees based on options
                $base_fee = $this->calculate_nft_mint_fee($options);
                break;
            default:
                $base_fee = 0;
        }
        
        // Apply user discount if applicable
        return $base_fee * (1 - $discount);
    }
    
    /**
     * Allocate transaction fees according to platform distribution policy
     *
     * @param float $total_fee Total fee amount in TOLA
     */
    private function allocate_transaction_fees($total_fee) {
        // 40% to platform maintenance and development
        $platform_portion = $total_fee * 0.4;
        $this->add_to_fee_allocation('platform', $platform_portion);
        
        // 30% to TOLA rewards pool
        $rewards_portion = $total_fee * 0.3;
        $this->add_to_fee_allocation('rewards', $rewards_portion);
        
        // 20% to AI training fund
        $ai_training_portion = $total_fee * 0.2;
        $this->add_to_fee_allocation('ai_training', $ai_training_portion);
        
        // 10% to charitable causes
        $charity_portion = $total_fee * 0.1;
        $this->add_to_fee_allocation('charity', $charity_portion);
    }
}
```

### 5. Transaction UI Components

```javascript
/**
 * Transaction fee display and selection component
 */
class TransactionFeeComponent {
    constructor(options) {
        this.transactionType = options.transactionType;
        this.amount = options.amount;
        this.sender = options.sender;
        this.recipient = options.recipient;
        this.container = options.container;
        
        this.feeArrangementOptions = [
            { value: 'sender_pays', label: 'I\'ll pay the entire fee' },
            { value: 'recipient_pays', label: 'Request recipient to pay fee' },
            { value: 'split', label: 'Split fee 50/50' }
        ];
        
        this.accelerationOptions = [
            { value: 0, label: 'Standard Processing (Included)', tolaPoints: 0 },
            { value: 1, label: 'Priority Processing', tolaPoints: 10 },
            { value: 2, label: 'Express Processing', tolaPoints: 25 }
        ];
        
        this.init();
    }
    
    /**
     * Initialize the component
     */
    init() {
        this.fetchFeeData()
            .then(data => {
                this.renderFeeUI(data);
                this.attachEventListeners();
            })
            .catch(error => {
                console.error('Error loading fee data:', error);
                this.renderErrorState();
            });
    }
    
    /**
     * Fetch transaction fee data from the server
     */
    async fetchFeeData() {
        const response = await fetch('/wp-json/vortex/v1/transaction/fee-estimate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                transaction_type: this.transactionType,
                amount: this.amount,
                sender_id: this.sender,
                recipient_id: this.recipient
            })
        });
        
        if (!response.ok) {
            throw new Error('Failed to fetch fee data');
        }
        
        return response.json();
    }
    
    /**
     * Render the fee UI with the provided data
     */
    renderFeeUI(data) {
        const feeInUSD = data.fee_usd;
        const feeInTOLA = data.fee_tola;
        
        const html = `
            <div class="vortex-transaction-fee-container">
                <h3>Transaction Fee</h3>
                <div class="fee-amount">
                    <span class="fee-usd">$${feeInUSD.toFixed(2)}</span>
                    <span class="fee-tola">(${feeInTOLA.toFixed(2)} TOLA)</span>
                </div>
                
                <div class="fee-arrangement">
                    <h4>Who pays the fee?</h4>
                    <div class="fee-options">
                        ${this.feeArrangementOptions.map(option => `
                            <div class="fee-option">
                                <input type="radio" name="fee_arrangement" id="fee_${option.value}" value="${option.value}" ${option.value === 'sender_pays' ? 'checked' : ''}>
                                <label for="fee_${option.value}">${option.label}</label>
                            </div>
                        `).join('')}
                    </div>
                </div>
                
                <div class="transaction-acceleration">
                    <h4>Transaction Speed</h4>
                    <div class="acceleration-options">
                        ${this.accelerationOptions.map(option => `
                            <div class="acceleration-option">
                                <input type="radio" name="acceleration" id="acceleration_${option.value}" value="${option.value}" ${option.value === 0 ? 'checked' : ''}>
                                <label for="acceleration_${option.value}">
                                    ${option.label}
                                    ${option.tolaPoints > 0 ? `<span class="tola-cost">+${option.tolaPoints} TOLA</span>` : ''}
                                </label>
                            </div>
                        `).join('')}
                    </div>
                </div>
                
                <div class="transaction-summary">
                    <h4>Summary</h4>
                    <div class="summary-details">
                        <div class="summary-row">
                            <span>Transaction Amount:</span>
                            <span>${this.amount.toFixed(2)} ${data.currency}</span>
                        </div>
                        <div class="summary-row">
                            <span>Base Fee:</span>
                            <span>$${feeInUSD.toFixed(2)}</span>
                        </div>
                        <div class="summary-row acceleration-fee" style="display: none;">
                            <span>Acceleration:</span>
                            <span>0 TOLA</span>
                        </div>
                        <div class="summary-row">
                            <span>Your Portion:</span>
                            <span class="your-portion">$${feeInUSD.toFixed(2)}</span>
                        </div>
                        <div class="summary-row">
                            <span>Recipient Portion:</span>
                            <span class="recipient-portion">$0.00</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        this.container.innerHTML = html;
    }
    
    /**
     * Attach event listeners to UI elements
     */
    attachEventListeners() {
        // Fee arrangement selection
        const feeOptions = this.container.querySelectorAll('input[name="fee_arrangement"]');
        feeOptions.forEach(option => {
            option.addEventListener('change', () => this.updateSummary());
        });
        
        // Acceleration selection
        const accelerationOptions = this.container.querySelectorAll('input[name="acceleration"]');
        accelerationOptions.forEach(option => {
            option.addEventListener('change', () => this.updateSummary());
        });
    }
    
    /**
     * Update the summary based on selected options
     */
    updateSummary() {
        const selectedFeeArrangement = this.container.querySelector('input[name="fee_arrangement"]:checked').value;
        const selectedAcceleration = parseInt(this.container.querySelector('input[name="acceleration"]:checked').value);
        
        const feeInUSD = parseFloat(this.container.querySelector('.fee-usd').textContent.replace('$', ''));
        
        let senderPortion = 0;
        let recipientPortion = 0;
        
        switch (selectedFeeArrangement) {
            case 'split':
                senderPortion = feeInUSD / 2;
                recipientPortion = feeInUSD / 2;
                break;
            case 'recipient_pays':
                senderPortion = 0;
                recipientPortion = feeInUSD;
                break;
            case 'sender_pays':
            default:
                senderPortion = feeInUSD;
                recipientPortion = 0;
                break;
        }
        
        // Update the summary
        this.container.querySelector('.your-portion').textContent = `$${senderPortion.toFixed(2)}`;
        this.container.querySelector('.recipient-portion').textContent = `$${recipientPortion.toFixed(2)}`;
        
        // Update acceleration fee if applicable
        const accelerationFeeRow = this.container.querySelector('.acceleration-fee');
        const accelerationTolaAmount = this.accelerationOptions.find(o => o.value === selectedAcceleration).tolaPoints;
        
        if (accelerationTolaAmount > 0) {
            accelerationFeeRow.style.display = 'flex';
            accelerationFeeRow.querySelector('span:last-child').textContent = `${accelerationTolaAmount} TOLA`;
        } else {
            accelerationFeeRow.style.display = 'none';
        }
    }
}
```

## Best Practices

### For Artists Who Collect

1. **Maintain Distinct Portfolios**: Keep your created works and collected works clearly separated
2. **Leverage Dual Insights**: Use collector insights to inform your artistic direction
3. **Build Reciprocal Relationships**: Support fellow artists through collecting to build community
4. **Balance Creation and Acquisition**: Set clear budgets and time allocations for both roles
5. **Use Role-Specific Analytics**: Review both creation and collection analytics separately

### For Collectors Who Create

1. **Define Your Primary Focus**: Decide whether collecting or creating is your primary activity
2. **Use Creation to Inform Collecting**: Let your creative process guide your collection development
3. **Highlight Dual Perspective**: Share insights from both perspectives in community discussions
4. **Track Separate Metrics**: Monitor success in both roles using appropriate metrics
5. **Leverage TOLA Across Roles**: Use TOLA points strategically across both roles

### For Platform Navigation

1. **Use Role-Appropriate Features**: Access features designed for your current active role
2. **Save Role-Specific Settings**: Customize each role interface independently
3. **Maintain Context Awareness**: Be mindful of which role context you're currently in
4. **Use Quick Switching**: Master the swipe gestures for efficient role switching
5. **Sync Shared Data**: Keep profile information consistent across roles 