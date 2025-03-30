import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Grid, Paper, Typography } from '@material-ui/core';
import { Line } from 'react-chartjs-2';
import { makeStyles } from '@material-ui/core/styles';
import ArtworkGrid from './ArtworkGrid';
import MarketTrends from './MarketTrends';
import ArtistInsights from './ArtistInsights';

const useStyles = makeStyles((theme) => ({
    root: {
        flexGrow: 1,
        padding: theme.spacing(3),
    },
    paper: {
        padding: theme.spacing(2),
        textAlign: 'center',
        color: theme.palette.text.secondary,
    },
    chart: {
        marginBottom: theme.spacing(3),
    }
}));

export default function Dashboard() {
    const classes = useStyles();
    const [marketData, setMarketData] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        // Fetch market data from WordPress REST API
        fetch('/wp-json/vortex-ai/v1/market-data')
            .then(response => response.json())
            .then(data => {
                setMarketData(data);
                setLoading(false);
            })
            .catch(error => {
                console.error('Error fetching market data:', error);
                setLoading(false);
            });
    }, []);

    if (loading) {
        return <div>{__('Loading...', 'vortex-ai-agents')}</div>;
    }

    return (
        <div className={classes.root}>
            <Grid container spacing={3}>
                <Grid item xs={12}>
                    <Paper className={classes.paper}>
                        <Typography variant="h4" gutterBottom>
                            {__('Art Market Dashboard', 'vortex-ai-agents')}
                        </Typography>
                    </Paper>
                </Grid>
                <Grid item xs={12} md={8}>
                    <Paper className={classes.paper}>
                        <MarketTrends data={marketData?.trends} />
                    </Paper>
                </Grid>
                <Grid item xs={12} md={4}>
                    <Paper className={classes.paper}>
                        <ArtistInsights data={marketData?.artists} />
                    </Paper>
                </Grid>
                <Grid item xs={12}>
                    <Paper className={classes.paper}>
                        <ArtworkGrid artworks={marketData?.artworks} />
                    </Paper>
                </Grid>
            </Grid>
        </div>
    );
} 