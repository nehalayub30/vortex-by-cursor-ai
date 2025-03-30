# VORTEX AI AGENTS: TOLA Metrics & Ranking System

This document provides detailed information about the metrics and daily ranking systems that power the TOLA (Token of Love and Appreciation) gamification features within the VORTEX AI AGENTS plugin.

## Table of Contents

- [Overview](#overview)
- [Core Metrics](#core-metrics)
- [Daily Rankings](#daily-rankings)
- [Visualization & Tracking](#visualization--tracking)
- [Progression System](#progression-system)
- [Seasonal Competitions](#seasonal-competitions)
- [Technical Implementation](#technical-implementation)
- [UI/UX Integration](#uiux-integration)
- [Best Practices](#best-practices)

## Overview

The TOLA metrics and ranking system is designed to:

1. **Drive Engagement**: Encourage daily platform usage through competitive elements
2. **Reward Contribution**: Provide tangible benefits for positive community contributions
3. **Measure Growth**: Track personal and professional development over time
4. **Guide Development**: Help users identify areas for improvement in their artistic practice
5. **Build Community**: Foster connections through healthy competition and collaboration

## Core Metrics

The platform tracks a comprehensive set of metrics that evaluate different aspects of user activity and contribution. These metrics form the foundation of rankings and progression.

### Artist Metrics

| Metric | Description | Calculation Method | Max Score |
|--------|-------------|-------------------|-----------|
| Creation Frequency | Volume of artwork production | (Works created in time period) × 5-25 points | 500/week |
| Technical Quality | AI assessment of technical execution | Neural analysis of composition, technique, execution | 50/work |
| Stylistic Consistency | Coherence of artistic style | Style signature algorithm comparison | 40/portfolio |
| Originality | Uniqueness compared to platform database | Similarity analysis against art database | 50/work |
| Description Quality | Thoroughness of artwork metadata | Completeness + SEO optimization score | 30/work |
| Media Diversity | Range of artistic mediums explored | (Number of distinct mediums used) × 10 | 100 total |
| Subject Exploration | Diversity of themes and subjects | (Number of distinct subjects explored) × 5 | 100 total |
| Community Engagement | Interaction with other artists | (Meaningful comments + responses) × 3 | 300/week |

### Collector Metrics

| Metric | Description | Calculation Method | Max Score |
|--------|-------------|-------------------|-----------|
| Collection Volume | Number of works collected | (Works acquired in time period) × 10-30 | 600/month |
| Collection Coherence | Thematic/stylistic consistency | AI assessment of collection relationships | 100/collection |
| Collection Diversity | Range within cohesive framework | Balance of consistency and exploration | 80/collection |
| Trend Anticipation | Early support for trending artists | Lead time between acquisition and trend × multiplier | 200/acquisition |
| Documentation | Collection cataloging quality | Completeness of collection metadata | 50/collection |
| Market Acumen | Value appreciation of acquired works | Average % value increase of collection | Unlimited |
| TOLA Distribution | Support shown to creators | TOLA awarded to artists × 1.2 multiplier | Unlimited |
| Education Contribution | Knowledge sharing activities | Community guides, articles, insights created | 100/contribution |

### Community Metrics

| Metric | Description | Calculation Method | Max Score |
|--------|-------------|-------------------|-----------|
| Helpfulness | Assistance provided to others | Quality-weighted help interactions | 200/week |
| Collaboration | Participation in joint projects | Projects × scope × impact × 25-100 | 500/collaboration |
| Network Size | Active connections maintained | (Active bidirectional connections) × 2 | 1000 total |
| Feedback Quality | Value of feedback provided | AI-assessed helpfulness + recipient rating | 50/feedback |
| Resource Sharing | Useful resources contributed | Resources × usefulness rating × 10 | 300/month |
| Event Participation | Activity in platform events | Events × participation level × 15 | 450/month |
| Community Building | Initiatives that strengthen community | Initiative impact score | 500/initiative |
| Mentorship | Guidance provided to new users | Mentorship hours × effectiveness rating × at | 400/month |

## Daily Rankings

The platform maintains multiple daily leaderboards that reset every 24 hours at midnight UTC, promoting regular participation and providing achievable short-term goals.

### Ranking Categories

The daily rankings are organized into three main groups with specific categories in each:

#### Creator Rankings

| Category | Calculation Factors | Update Frequency |
|----------|---------------------|------------------|
| Most Creative | Originality score + Style distinctiveness + AI novelty assessment | Real-time |
| Most Prolific | Creation count × quality modifier | Real-time |
| Most Appreciated | TOLA received + Weighted comments + Saves | Hourly |
| Rising Star | Recent growth rate in profile views, followers, and appreciation | Daily |

#### Collector Rankings

| Category | Calculation Factors | Update Frequency |
|----------|---------------------|------------------|
| Top Curator | Collection coherence score + Curator influence rating | Daily |
| Most Generous | TOLA awarded + Meaningful feedback provided | Hourly |
| Trend Spotter | Support for artists before popularity increases | Daily |
| Collection Growth | Collection value appreciation + New acquisitions quality | Daily |

#### Community Rankings

| Category | Calculation Factors | Update Frequency |
|----------|---------------------|------------------|
| Most Helpful | Help metrics + User gratitude indicators | Hourly |
| Most Connected | Network size × engagement depth | Daily |
| Most Influential | Content reach × engagement × conversation generation | Daily |
| Most Engaged | Platform time × meaningful interactions × breadth of activity | Hourly |

### Ranking Rewards

Daily and weekly rewards incentivize consistent participation and excellence:

#### Daily Rewards

| Ranking Position | TOLA Reward | Badge | Visibility Boost | Special Access |
|------------------|-------------|-------|------------------|----------------|
| 1st Place | 100 TOLA | Daily Gold | Top of category lists | 24-hour spotlight |
| 2nd-5th Place | 50 TOLA | Daily Silver | Enhanced discovery | Featured section |
| 6th-20th Place | 25 TOLA | Daily Bronze | Modest boost | Community highlight |
| 21st-100th Place | 10 TOLA | Recognition | Minor boost | Honorable mention |

#### Weekly Cumulative Rewards

| Weekly Performance | TOLA Reward | Special Benefit |
|-------------------|-------------|----------------|
| Top Performer | 500 TOLA | Featured profile for 7 days |
| Top 5 Consistent | 250 TOLA | 7-day visibility boost |
| Top 20 Consistent | 100 TOLA | Special achievement badge |
| Top 100 Consistent | 50 TOLA | Community newsletter feature |

### Streak Bonuses

Consecutive days in rankings receive compounding benefits:

| Streak Length | Bonus Multiplier | Additional Perks |
|--------------|------------------|------------------|
| 3-6 days | 1.2× TOLA rewards | Special streak indicator |
| 7-13 days | 1.5× TOLA rewards | Bonus badges |
| 14-20 days | 2.0× TOLA rewards | Early access to new features |
| 21+ days | 3.0× TOLA rewards | VIP status indicators |

## Visualization & Tracking

The platform provides comprehensive tools for users to track their metrics and rankings over time.

### Metric Visualization Tools

#### Radar Charts

- **Personal Radar**: Shows strengths across 8 core metrics
- **Comparison Radar**: Overlays personal metrics with community averages
- **Historical Radar**: Shows metric development over time
- **Goal Radar**: Displays target metrics against current performance

#### Performance Graphs

- **Metric Trends**: Line graphs showing metric development over time
- **Ranking History**: Position tracking for each ranking category
- **Weekly Patterns**: Heatmaps showing performance by day of week
- **Activity Impact**: Correlation between activities and metric changes

#### Achievement Tracking

- **Progress Bars**: Visual indicators for achievement completion
- **Badge Collection**: Gallery of earned badges with acquisition dates
- **Milestone Timeline**: Chronological display of achievements
- **Next Goals**: Suggested achievements within reach

#### Community Comparison

- **Percentile Indicators**: Relative standing within community
- **Peer Comparison**: Anonymous comparison with similar users
- **Growth Rate**: Progression speed compared to community average
- **Strength Analysis**: Identification of standout metrics

### Daily Ranking Interface

The daily ranking interface provides:

1. **Live Leaderboards**: Real-time position updates
2. **Position Tracking**: Historical position charting
3. **Category Filtering**: View rankings by specific categories
4. **Friend Focus**: Filter to see only connections
5. **Reward History**: Record of rewards earned from rankings
6. **Ranking Analytics**: Performance insights and improvement suggestions
7. **Notification Settings**: Customizable alerts for ranking changes
8. **Share Options**: Social sharing of ranking achievements

## Progression System

The platform uses a tiered progression system that recognizes cumulative achievement and provides meaningful benefits at each level.

### Artist Progression Path

| Tier | Name | Requirements | Benefits |
|------|------|-------------|----------|
| 1 | Novice | New account | Access to basic creation tools |
| 2 | Emerging | 500 TOLA + 10 quality works | +10% visibility, 5% fee reduction |
| 3 | Established | 2,000 TOLA + 25 quality works | +25% visibility, priority HURAII access |
| 4 | Professional | 5,000 TOLA + 50 quality works | Featured status, VIP support, 15% fee reduction |
| 5 | Master | 10,000 TOLA + 100 quality works | Curator abilities, exclusive features, 25% fee reduction |
| 6 | Luminary | 25,000 TOLA + community recognition | Platform ambassadorship, mentorship program access |

### Collector Progression Path

| Tier | Name | Requirements | Benefits |
|------|------|-------------|----------|
| 1 | Browser | New account | Basic collecting features |
| 2 | Enthusiast | 500 TOLA + 5 acquisitions | Early access notifications, 5% fee reduction |
| 3 | Connoisseur | 2,000 TOLA + 15 acquisitions | Private viewing rooms, market insight reports |
| 4 | Patron | 5,000 TOLA + 30 acquisitions | VIP event invitations, 15% fee reduction |
| 5 | Luminary | 10,000 TOLA + 50 acquisitions | Influencer recommendations, exclusive deals |
| 6 | Benefactor | 25,000 TOLA + significant impact | Legacy program, institutional partnership opportunities |

### Specialization Paths

Users can pursue specialized recognition in specific areas:

#### Artist Specializations

- **Innovator**: Focus on originality and pushing boundaries
- **Master Technician**: Excellence in technical execution
- **Community Pillar**: Leadership in artist community
- **Prolific Creator**: Consistent high-quality production
- **Style Pioneer**: Development of distinctive style

#### Collector Specializations

- **Tastemaker**: Exceptional curation and trend prediction
- **Patron**: Substantial support for emerging artists
- **Archivist**: Excellence in collection documentation
- **Connector**: Building bridges between artists and collectors
- **Educator**: Sharing knowledge and insights with community

## Seasonal Competitions

The platform organizes its gamification into thematic seasons that provide structure and variety to the competitive elements.

### Season Structure

- **Duration**: 12 weeks (quarterly)
- **Theme**: Each season has a unifying artistic or collecting theme
- **Challenges**: Weekly and seasonal objectives aligned with theme
- **Special Events**: Themed exhibitions, collaborations, and competitions
- **Culmination**: End-of-season recognition ceremony and rewards

### Season Rewards

Seasonal performance earns exclusive rewards:

1. **Digital Collectibles**: Limited edition digital artworks
2. **Profile Enhancements**: Seasonal frames, backgrounds, and effects
3. **TOLA Bonuses**: Substantial TOLA rewards for top performers
4. **Physical Rewards**: Real-world merchandise, prints, or art supplies
5. **Exhibition Opportunities**: Feature in seasonal showcase exhibitions
6. **Educational Access**: Premium workshops and master classes

### Season Leaderboards

Seasonal performance is tracked through:

1. **Cumulative Scoring**: Points accumulated throughout the season
2. **Category Excellence**: Top performance in specific categories
3. **Improvement Recognition**: Greatest growth from previous season
4. **Consistency Awards**: Rewards for steady performance
5. **Special Challenge Completion**: Recognition for themed challenges

## Technical Implementation

The metrics and ranking system is implemented through several interconnected components:

### Database Schema

```sql
-- Daily rankings table
CREATE TABLE wp_vortex_daily_rankings (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT(20) UNSIGNED NOT NULL,
    category VARCHAR(50) NOT NULL,
    date DATE NOT NULL,
    score DECIMAL(10,2) NOT NULL,
    rank INT(11) NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY user_category_date (user_id, category, date),
    KEY category_date_rank (category, date, rank)
);

-- User metrics table
CREATE TABLE wp_vortex_user_metrics (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT(20) UNSIGNED NOT NULL,
    metric_key VARCHAR(50) NOT NULL,
    metric_value DECIMAL(10,2) NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY user_metric (user_id, metric_key),
    KEY metric_value (metric_key, metric_value)
);

-- Metric history for tracking changes
CREATE TABLE wp_vortex_metric_history (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT(20) UNSIGNED NOT NULL,
    metric_key VARCHAR(50) NOT NULL,
    old_value DECIMAL(10,2) NOT NULL,
    new_value DECIMAL(10,2) NOT NULL,
    change_date DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY user_metric_date (user_id, metric_key, change_date)
);

-- Ranking rewards history
CREATE TABLE wp_vortex_ranking_rewards (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT(20) UNSIGNED NOT NULL,
    category VARCHAR(50) NOT NULL,
    position INT(11) NOT NULL,
    tola_amount INT(11) NOT NULL,
    badge_key VARCHAR(50) NULL,
    reward_date DATE NOT NULL,
    PRIMARY KEY (id),
    KEY user_date (user_id, reward_date)
);
```

### Core Classes

#### Metrics Calculator

```php
/**
 * Calculates and updates user metrics
 */
class Vortex_Metrics_Calculator {
    /**
     * Update user metrics based on activity
     */
    public function update_user_metrics($user_id, $event_type, $event_data) {
        // Get current metrics
        $current_metrics = $this->get_user_metrics($user_id);
        
        // Calculate new metrics based on event
        $updated_metrics = $this->calculate_updated_metrics(
            $current_metrics,
            $event_type,
            $event_data
        );
        
        // Save updated metrics
        $this->save_user_metrics($user_id, $updated_metrics);
        
        // Record metric history
        $this->record_metric_changes(
            $user_id,
            $current_metrics,
            $updated_metrics
        );
        
        // Check for milestone achievements
        $this->check_metric_achievements($user_id, $updated_metrics);
        
        return $updated_metrics;
    }
    
    /**
     * Calculate artist-specific metrics
     */
    private function calculate_artist_metrics($user_id, $metrics_data) {
        // Implementation for artist metrics calculation
    }
    
    /**
     * Calculate collector-specific metrics
     */
    private function calculate_collector_metrics($user_id, $metrics_data) {
        // Implementation for collector metrics calculation
    }
    
    /**
     * Calculate community metrics
     */
    private function calculate_community_metrics($user_id, $metrics_data) {
        // Implementation for community metrics calculation
    }
}
```

#### Rankings Manager

```php
/**
 * Manages daily rankings calculation and rewards
 */
class Vortex_Rankings_Manager {
    /**
     * Calculate and update all rankings
     */
    public function update_all_rankings() {
        $categories = $this->get_ranking_categories();
        
        foreach ($categories as $category) {
            $this->calculate_category_rankings($category);
        }
    }
    
    /**
     * Calculate rankings for a specific category
     */
    private function calculate_category_rankings($category) {
        global $wpdb;
        
        // Get all eligible users and their scores
        $users = $this->get_category_user_scores($category);
        
        // Sort by score
        usort($users, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        // Assign ranks
        $rank = 1;
        foreach ($users as $index => $user) {
            // Handle ties
            if ($index > 0 && $user['score'] === $users[$index - 1]['score']) {
                // Same score as previous user, assign same rank
                $user_rank = $users[$index - 1]['rank'];
            } else {
                $user_rank = $rank;
            }
            
            $this->save_user_ranking(
                $user['user_id'],
                $category,
                $user['score'],
                $user_rank
            );
            
            $rank++;
        }
    }
    
    /**
     * Process daily ranking rewards
     */
    public function process_daily_rewards() {
        // Implementation for awarding daily rewards
    }
    
    /**
     * Process weekly ranking rewards
     */
    public function process_weekly_rewards() {
        // Implementation for awarding weekly rewards
    }
}
```

#### Visualization Service

```php
/**
 * Handles metric visualization data preparation
 */
class Vortex_Metric_Visualization {
    /**
     * Get radar chart data for user
     */
    public function get_radar_chart_data($user_id, $comparison_type = 'community') {
        // Implementation for radar chart data
    }
    
    /**
     * Get ranking history data
     */
    public function get_ranking_history($user_id, $category, $days = 30) {
        // Implementation for ranking history
    }
    
    /**
     * Get metric trend data
     */
    public function get_metric_trends($user_id, $metric_keys, $timeframe = '3months') {
        // Implementation for metric trends
    }
}
```

### Cron Jobs

```php
// Register cron schedules
add_filter('cron_schedules', function($schedules) {
    $schedules['hourly_rankings'] = [
        'interval' => 3600,
        'display' => __('Every Hour (Rankings)')
    ];
    
    $schedules['midnight_utc'] = [
        'interval' => 86400,
        'display' => __('Once Daily at Midnight UTC')
    ];
    
    return $schedules;
});

// Schedule ranking updates
if (!wp_next_scheduled('vortex_hourly_ranking_update')) {
    wp_schedule_event(time(), 'hourly_rankings', 'vortex_hourly_ranking_update');
}

if (!wp_next_scheduled('vortex_daily_ranking_reset')) {
    // Schedule at midnight UTC
    $midnight_utc = strtotime('tomorrow midnight UTC');
    wp_schedule_event($midnight_utc, 'midnight_utc', 'vortex_daily_ranking_reset');
}

if (!wp_next_scheduled('vortex_weekly_rewards')) {
    // Schedule for Sunday midnight UTC
    $sunday_midnight = strtotime('next Sunday midnight UTC');
    wp_schedule_event($sunday_midnight, 'weekly', 'vortex_weekly_rewards');
}

// Cron handlers
add_action('vortex_hourly_ranking_update', function() {
    $rankings = new Vortex_Rankings_Manager();
    $rankings->update_all_rankings();
});

add_action('vortex_daily_ranking_reset', function() {
    $rankings = new Vortex_Rankings_Manager();
    $rankings->process_daily_rewards();
    $rankings->reset_daily_rankings();
});

add_action('vortex_weekly_rewards', function() {
    $rankings = new Vortex_Rankings_Manager();
    $rankings->process_weekly_rewards();
});
```

## UI/UX Integration

The metrics and ranking system is integrated throughout the platform UI for maximum visibility and engagement.

### Dashboard Widgets

1. **Metrics Summary Widget**:
   - Compact view of key metrics
   - Visual indicators for trends
   - Quick links to detailed metrics

2. **Daily Ranking Widget**:
   - Current positions in relevant categories
   - Position changes since previous day
   - Next ranking level targets

3. **Achievement Progress Widget**:
   - Next achievements within reach
   - Progress bars for ongoing goals
   - Recently earned achievements

4. **Community Standing Widget**:
   - Percentile position in community
   - Comparison with similar users
   - Trend indicators for community position

### Interactive Elements

1. **Live Updates**:
   - Position changes update in real-time
   - Toast notifications for significant changes
   - Animation for metric improvements

2. **Contextual Tips**:
   - Activity suggestions to improve low metrics
   - Congratulatory messages for achievements
   - Strategic advice based on current standings

3. **Personalized Goals**:
   - Suggested daily actions
   - Custom milestone setting
   - Streak protection reminders

4. **Social Components**:
   - Congratulatory messages to peers
   - Collaborative challenges
   - Shared milestone celebrations

### Visual Design

1. **Color Coding**:
   - Green for improvements
   - Red for declines
   - Gold for achievements
   - Blue for informational metrics

2. **Progress Indicators**:
   - Circular progress wheels
   - Horizontal progress bars
   - Milestone markers
   - Goal visualization

3. **Charts and Graphs**:
   - Radar charts for metrics overview
   - Line graphs for historical trends
   - Bar charts for comparative metrics
   - Heat maps for activity patterns

4. **Badges and Rewards**:
   - Visual hierarchy of achievements
   - Animated badge reveals
   - Collection showcase
   - Reward history timeline

## Best Practices

Guidelines for platform users to make the most of the metrics and ranking system:

### For Artists

1. **Focus on Quality Over Quantity**: While activity frequency is important, quality metrics have higher weighting in rankings
2. **Develop a Consistent Style**: Style consistency metrics reward cohesive artistic vision
3. **Engage Meaningfully**: Thoughtful community interactions count more than volume
4. **Complete Metadata**: Thoroughly document your work to maximize description quality metrics
5. **Set Personal Goals**: Competing with yourself often leads to more sustainable growth than competing with others

### For Collectors

1. **Develop Collection Themes**: Coherent collections score higher than random acquisitions
2. **Document Your Collection**: Complete metadata improves collection documentation metrics
3. **Support Emerging Artists**: Early support for rising talent improves trend anticipation scores
4. **Provide Thoughtful Feedback**: Quality feedback contributes to community metrics
5. **Balance Breadth and Depth**: Both specialization and diversity are rewarded in different metrics

### For Platform Usage

1. **Daily Consistency**: Short daily sessions are better for metrics than occasional long sessions
2. **Balanced Activity**: Engage across multiple platform areas for well-rounded metrics
3. **Track Trends**: Monitor your metrics visualization to identify patterns
4. **Leverage Strengths**: Focus on categories where you naturally excel for ranking rewards
5. **Community First**: The most sustainable way to improve metrics is to genuinely contribute to the community 