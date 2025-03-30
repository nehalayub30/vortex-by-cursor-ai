# VORTEX AI AGENTS API Documentation

## API Overview

The VORTEX AI AGENTS plugin provides a RESTful API for accessing art market analytics and insights. All endpoints are accessible through the WordPress REST API with the base path `/wp-json/vortex-ai/v1/`.

## Authentication

All API endpoints require authentication. The plugin supports:
- WordPress cookie authentication
- Application passwords
- JWT authentication (with compatible plugin)

## Rate Limiting

- 100 requests per hour per user
- 1000 requests per day per user
- Batch endpoints count as multiple requests based on the number of items

## Endpoints

### 1. Get Artwork Analytics

```http
GET /wp-json/vortex-ai/v1/artwork-analytics/{id}
```

Retrieves comprehensive analytics for a specific artwork.

#### Parameters

| Name | Type | Description |
|------|------|-------------|
| id   | integer | The artwork ID |

#### Response

```json
{
    "market_fit": {
        "overall_score": 0.85,
        "style_match": 0.9,
        "price_match": 0.8,
        "demand_score": 0.85,
        "market_potential": 0.87
    },
    "price_analysis": {
        "current_price": 5000,
        "optimal_price": 5500,
        "price_competitiveness": 0.85,
        "price_elasticity": 0.7,
        "comparable_works": [
            {
                "id": 123,
                "price": 4800,
                "date": "2024-01-15"
            }
        ]
    },
    "trend_alignment": {
        "current_alignment": 0.88,
        "future_potential": 0.92,
        "trend_duration": "6 months",
        "market_momentum": 0.75,
        "current_trends": [
            {
                "name": "Abstract Expressionism",
                "strength": 0.85
            }
        ],
        "future_trends": [
            {
                "name": "Digital Integration",
                "confidence": 85
            }
        ]
    },
    "audience_match": {
        "segments": [
            {
                "name": "Contemporary Collectors",
                "percentage": 45
            }
        ],
        "engagement_metrics": {
            "view_rate": 0.75,
            "inquiry_rate": 0.15,
            "conversion_rate": 0.05
        }
    }
}
```

### 2. Batch Analytics

```http
POST /wp-json/vortex-ai/v1/artwork-analytics/batch
```

Retrieves analytics for multiple artworks in a single request.

#### Request Body

```json
{
    "artwork_ids": [1, 2, 3]
}
```

#### Response

```json
{
    "1": {
        // Artwork 1 analytics (same structure as single artwork)
    },
    "2": {
        // Artwork 2 analytics
    },
    "3": {
        // Artwork 3 analytics
    }
}
```

### 3. Category Analytics

```http
GET /wp-json/vortex-ai/v1/artwork-analytics/category/{category}
```

Retrieves market context and trends for a specific artwork category.

#### Parameters

| Name | Type | Description |
|------|------|-------------|
| category | string | The artwork category |

#### Response

```json
{
    "market_size": 1000000,
    "growth_rate": 0.15,
    "buyer_demographics": {
        "age_groups": {
            "25-34": 0.2,
            "35-44": 0.35,
            "45-54": 0.3,
            "55+": 0.15
        },
        "locations": {
            "North America": 0.4,
            "Europe": 0.35,
            "Asia": 0.25
        }
    },
    "trend_indicators": {
        "current_trends": [
            {
                "name": "Minimalism",
                "strength": 0.8
            }
        ],
        "emerging_trends": [
            {
                "name": "Sustainable Art",
                "growth_rate": 0.25
            }
        ]
    },
    "competition_level": {
        "score": 0.75,
        "active_artists": 150,
        "market_saturation": 0.65
    }
}
```

## Error Handling

The API uses standard HTTP status codes and returns error messages in a consistent format:

```json
{
    "code": "error_code",
    "message": "Human-readable error message",
    "data": {
        "status": 400
    }
}
```

Common error codes:

| Code | Description |
|------|-------------|
| 400  | Bad Request |
| 401  | Unauthorized |
| 403  | Forbidden |
| 404  | Not Found |
| 429  | Too Many Requests |
| 500  | Internal Server Error |

## Pagination

For endpoints that return lists, pagination is supported using the following parameters:

| Parameter | Type | Description |
|-----------|------|-------------|
| page      | integer | Page number (default: 1) |
| per_page  | integer | Items per page (default: 10, max: 100) |

Response headers include:

- `X-WP-Total`: Total number of items
- `X-WP-TotalPages`: Total number of pages

## Filtering

Some endpoints support filtering using query parameters:

```http
GET /wp-json/vortex-ai/v1/artwork-analytics?price_min=1000&price_max=5000&style=abstract
```

## Versioning

The API is versioned in the URL path. The current version is `v1`. Breaking changes will be introduced in new versions.

## SDK

A PHP SDK is available for easier integration:

```php
use VortexAIAgents\SDK\VortexAPI;

$api = new VortexAPI('your-api-key');
$analytics = $api->getArtworkAnalytics(123);
```

## Rate Limiting Headers

Response headers include rate limit information:

- `X-RateLimit-Limit`: Requests limit
- `X-RateLimit-Remaining`: Remaining requests
- `X-RateLimit-Reset`: Time until limit reset

## Webhook Support

The API supports webhooks for real-time updates:

1. Register webhook URL:
```http
POST /wp-json/vortex-ai/v1/webhooks
{
    "url": "https://your-domain.com/webhook",
    "events": ["analytics.updated", "trend.detected"]
}
```

2. Webhook payload example:
```json
{
    "event": "analytics.updated",
    "artwork_id": 123,
    "timestamp": "2024-01-20T15:30:00Z",
    "data": {
        // Updated analytics data
    }
}
```

## Best Practices

1. **Caching**
   - Cache responses when possible
   - Use ETags for cache validation
   - Respect cache headers

2. **Rate Limiting**
   - Implement exponential backoff
   - Use batch endpoints for multiple items
   - Monitor rate limit headers

3. **Error Handling**
   - Implement proper error handling
   - Log API errors appropriately
   - Provide user-friendly error messages

## Support

For API support:
- Email: api-support@vortexcartec.com
- Documentation: https://docs.vortexcartec.com/api
- Status: https://status.vortexcartec.com 