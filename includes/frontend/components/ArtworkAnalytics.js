import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
    Grid,
    Paper,
    Typography,
    CircularProgress,
    Tabs,
    Tab,
    Box,
    Chip,
    Divider,
} from '@material-ui/core';
import { makeStyles } from '@material-ui/core/styles';
import { Line, Radar, Doughnut } from 'react-chartjs-2';
import TrendingUpIcon from '@material-ui/icons/TrendingUp';
import TimelineIcon from '@material-ui/icons/Timeline';
import GroupIcon from '@material-ui/icons/Group';
import AttachMoneyIcon from '@material-ui/icons/AttachMoney';

const useStyles = makeStyles((theme) => ({
    root: {
        flexGrow: 1,
        padding: theme.spacing(3),
    },
    paper: {
        padding: theme.spacing(2),
        height: '100%',
    },
    tabPanel: {
        padding: theme.spacing(2),
    },
    chip: {
        margin: theme.spacing(0.5),
    },
    divider: {
        margin: theme.spacing(2, 0),
    },
    scoreValue: {
        fontSize: '2rem',
        fontWeight: 'bold',
        color: theme.palette.primary.main,
    },
    metric: {
        display: 'flex',
        alignItems: 'center',
        marginBottom: theme.spacing(1),
    },
    metricIcon: {
        marginRight: theme.spacing(1),
        color: theme.palette.secondary.main,
    },
}));

function TabPanel({ children, value, index }) {
    return (
        <div hidden={value !== index} className="tab-panel">
            {value === index && <Box p={3}>{children}</Box>}
        </div>
    );
}

export default function ArtworkAnalytics({ artworkId }) {
    const classes = useStyles();
    const [value, setValue] = useState(0);
    const [loading, setLoading] = useState(true);
    const [analytics, setAnalytics] = useState(null);
    const [error, setError] = useState(null);

    useEffect(() => {
        fetch(`/wp-json/vortex-ai/v1/artwork-analytics/${artworkId}`)
            .then((response) => response.json())
            .then((data) => {
                setAnalytics(data);
                setLoading(false);
            })
            .catch((err) => {
                setError(err.message);
                setLoading(false);
            });
    }, [artworkId]);

    const handleTabChange = (event, newValue) => {
        setValue(newValue);
    };

    if (loading) {
        return (
            <Box display="flex" justifyContent="center" p={3}>
                <CircularProgress />
            </Box>
        );
    }

    if (error) {
        return (
            <Paper className={classes.paper}>
                <Typography color="error">{error}</Typography>
            </Paper>
        );
    }

    const marketFitData = {
        labels: [
            __('Style Match', 'vortex-ai-agents'),
            __('Price Match', 'vortex-ai-agents'),
            __('Demand Score', 'vortex-ai-agents'),
            __('Market Potential', 'vortex-ai-agents'),
            __('Trend Alignment', 'vortex-ai-agents'),
        ],
        datasets: [
            {
                label: __('Market Fit Analysis', 'vortex-ai-agents'),
                data: [
                    analytics.market_fit.style_match,
                    analytics.market_fit.price_match,
                    analytics.market_fit.demand_score,
                    analytics.market_fit.market_potential,
                    analytics.trend_alignment.current_alignment,
                ],
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1,
            },
        ],
    };

    const priceHistoryData = {
        labels: analytics.price_analysis.comparable_works.map((work) => work.date),
        datasets: [
            {
                label: __('Price History', 'vortex-ai-agents'),
                data: analytics.price_analysis.comparable_works.map((work) => work.price),
                fill: false,
                borderColor: 'rgb(255, 99, 132)',
                tension: 0.1,
            },
        ],
    };

    return (
        <div className={classes.root}>
            <Grid container spacing={3}>
                <Grid item xs={12}>
                    <Paper className={classes.paper}>
                        <Typography variant="h4" gutterBottom>
                            {__('Artwork Analytics', 'vortex-ai-agents')}
                        </Typography>
                        <Tabs value={value} onChange={handleTabChange} indicatorColor="primary" textColor="primary">
                            <Tab label={__('Market Fit', 'vortex-ai-agents')} icon={<TrendingUpIcon />} />
                            <Tab label={__('Price Analysis', 'vortex-ai-agents')} icon={<AttachMoneyIcon />} />
                            <Tab label={__('Trends', 'vortex-ai-agents')} icon={<TimelineIcon />} />
                            <Tab label={__('Audience', 'vortex-ai-agents')} icon={<GroupIcon />} />
                        </Tabs>

                        <TabPanel value={value} index={0}>
                            <Grid container spacing={3}>
                                <Grid item xs={12} md={6}>
                                    <Radar data={marketFitData} />
                                </Grid>
                                <Grid item xs={12} md={6}>
                                    <Typography variant="h6" gutterBottom>
                                        {__('Market Fit Score', 'vortex-ai-agents')}
                                    </Typography>
                                    <Typography className={classes.scoreValue}>
                                        {(analytics.market_fit.overall_score * 100).toFixed(1)}%
                                    </Typography>
                                    <Divider className={classes.divider} />
                                    <Typography variant="subtitle1" gutterBottom>
                                        {__('Key Metrics', 'vortex-ai-agents')}
                                    </Typography>
                                    {Object.entries(analytics.market_fit).map(([key, value]) => (
                                        <div key={key} className={classes.metric}>
                                            <TrendingUpIcon className={classes.metricIcon} />
                                            <Typography>
                                                {key.replace('_', ' ')}: {(value * 100).toFixed(1)}%
                                            </Typography>
                                        </div>
                                    ))}
                                </Grid>
                            </Grid>
                        </TabPanel>

                        <TabPanel value={value} index={1}>
                            <Grid container spacing={3}>
                                <Grid item xs={12}>
                                    <Line data={priceHistoryData} />
                                </Grid>
                                <Grid item xs={12}>
                                    <Typography variant="h6" gutterBottom>
                                        {__('Price Analysis', 'vortex-ai-agents')}
                                    </Typography>
                                    <Grid container spacing={2}>
                                        <Grid item xs={12} md={6}>
                                            <Paper className={classes.paper}>
                                                <Typography variant="subtitle1">
                                                    {__('Current Price', 'vortex-ai-agents')}
                                                </Typography>
                                                <Typography className={classes.scoreValue}>
                                                    ${analytics.price_analysis.current_price.toLocaleString()}
                                                </Typography>
                                            </Paper>
                                        </Grid>
                                        <Grid item xs={12} md={6}>
                                            <Paper className={classes.paper}>
                                                <Typography variant="subtitle1">
                                                    {__('Optimal Price', 'vortex-ai-agents')}
                                                </Typography>
                                                <Typography className={classes.scoreValue}>
                                                    ${analytics.price_analysis.optimal_price.toLocaleString()}
                                                </Typography>
                                            </Paper>
                                        </Grid>
                                    </Grid>
                                </Grid>
                            </Grid>
                        </TabPanel>

                        <TabPanel value={value} index={2}>
                            <Grid container spacing={3}>
                                <Grid item xs={12} md={6}>
                                    <Typography variant="h6" gutterBottom>
                                        {__('Current Trends', 'vortex-ai-agents')}
                                    </Typography>
                                    {analytics.trend_alignment.current_trends.map((trend) => (
                                        <Chip
                                            key={trend.name}
                                            label={`${trend.name} (${(trend.strength * 100).toFixed(1)}%)`}
                                            className={classes.chip}
                                            color={trend.strength > 0.7 ? 'primary' : 'default'}
                                        />
                                    ))}
                                </Grid>
                                <Grid item xs={12} md={6}>
                                    <Typography variant="h6" gutterBottom>
                                        {__('Future Predictions', 'vortex-ai-agents')}
                                    </Typography>
                                    {analytics.trend_alignment.future_trends.map((trend) => (
                                        <Chip
                                            key={trend.name}
                                            label={`${trend.name} (${trend.confidence}%)`}
                                            className={classes.chip}
                                            color={trend.confidence > 70 ? 'secondary' : 'default'}
                                        />
                                    ))}
                                </Grid>
                            </Grid>
                        </TabPanel>

                        <TabPanel value={value} index={3}>
                            <Grid container spacing={3}>
                                <Grid item xs={12} md={6}>
                                    <Typography variant="h6" gutterBottom>
                                        {__('Audience Segments', 'vortex-ai-agents')}
                                    </Typography>
                                    <Doughnut
                                        data={{
                                            labels: analytics.audience_match.segments.map((segment) => segment.name),
                                            datasets: [
                                                {
                                                    data: analytics.audience_match.segments.map(
                                                        (segment) => segment.percentage
                                                    ),
                                                    backgroundColor: [
                                                        '#FF6384',
                                                        '#36A2EB',
                                                        '#FFCE56',
                                                        '#4BC0C0',
                                                        '#9966FF',
                                                    ],
                                                },
                                            ],
                                        }}
                                    />
                                </Grid>
                                <Grid item xs={12} md={6}>
                                    <Typography variant="h6" gutterBottom>
                                        {__('Engagement Metrics', 'vortex-ai-agents')}
                                    </Typography>
                                    {Object.entries(analytics.audience_match.engagement_metrics).map(([key, value]) => (
                                        <div key={key} className={classes.metric}>
                                            <GroupIcon className={classes.metricIcon} />
                                            <Typography>
                                                {key.replace('_', ' ')}: {value}
                                            </Typography>
                                        </div>
                                    ))}
                                </Grid>
                            </Grid>
                        </TabPanel>
                    </Paper>
                </Grid>
            </Grid>
        </div>
    );
} 