# Vortex AI Marketplace WordPress Plugin

A lightweight WordPress client plugin for the Vortex AI Marketplace SaaS platform. This plugin provides a user interface for displaying marketplace data and communicates with the SaaS backend via API calls.

## Description

The Vortex AI Marketplace plugin connects your WordPress site to the Vortex AI Marketplace SaaS platform. It provides a set of shortcodes and widgets that allow you to display marketplace data such as market predictions, asset performance, and artwork analysis directly on your WordPress site.

This is a client-only plugin, meaning that all the heavy processing, AI analysis, and business logic are handled by the SaaS backend. The plugin simply retrieves data from the API and displays it in a user-friendly format.

## Installation

1. Upload the `vortex-ai-marketplace` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to the Vortex AI settings page to configure your API credentials
4. Begin using the shortcodes and widgets to display marketplace data

## Configuration

After installing the plugin, you need to configure it with your API key and endpoint URL:

1. Go to Vortex AI > Settings in the WordPress admin menu
2. Enter your API key (obtained from your Vortex AI Marketplace account)
3. The API endpoint URL is pre-configured but can be changed if necessary
4. Save your settings

## Usage

### Shortcodes

The plugin provides the following shortcodes:

#### Market Predictions

```
[vortex_market_predictions asset_type="all" time_frame="7days"]
```

Displays market predictions for the specified asset type and time frame.

- `asset_type`: The type of asset to get predictions for (default: "all")
- `time_frame`: The time frame for predictions (options: "24h", "7days", "30days", "90days", "1year", default: "7days")

#### Asset Prediction

```
[vortex_asset_prediction asset_id="123" time_frame="7days"]
```

Displays predictions for a specific asset.

- `asset_id`: The ID of the asset to get predictions for (required)
- `time_frame`: The time frame for predictions (options: "24h", "7days", "30days", "90days", "1year", default: "7days")

#### Market Analytics

```
[vortex_market_analytics start_date="2023-01-01" end_date="2023-01-31"]
```

Displays market analytics for the specified date range.

- `start_date`: The start date for analytics (format: YYYY-MM-DD, default: 30 days ago)
- `end_date`: The end date for analytics (format: YYYY-MM-DD, default: today)

#### Artist Performance

```
[vortex_artist_performance artist_id="123" start_date="2023-01-01" end_date="2023-01-31"]
```

Displays performance metrics for a specific artist.

- `artist_id`: The ID of the artist (required)
- `start_date`: The start date for analytics (format: YYYY-MM-DD, default: 30 days ago)
- `end_date`: The end date for analytics (format: YYYY-MM-DD, default: today)

#### Artwork Analysis

```
[vortex_artwork_analysis artwork_id="123"]
```

Displays analysis for a specific artwork.

- `artwork_id`: The ID of the artwork to analyze (required)

## API Communication

The plugin communicates with the Vortex AI Marketplace SaaS backend using the WordPress HTTP API. All API requests are authenticated using your API key, which is securely stored in the WordPress database.

## Troubleshooting

If you encounter issues with the plugin, check the following:

1. Make sure your API key is correct and active
2. Verify that the API endpoint URL is correct
3. Check your server's outbound connections to make sure it can reach the API endpoint
4. Look for any error messages displayed in the plugin's settings page

## Support

For support, please contact support@vortexartec.com or visit our website at https://www.vortexartec.com.

## License

This plugin is licensed under the GPL v2 or later.

## Credits

* Developed by the Vortex Development Team
