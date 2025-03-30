import { __ } from '@wordpress/i18n';
import { Line } from 'react-chartjs-2';
import { Typography, Box } from '@material-ui/core';
import { makeStyles } from '@material-ui/core/styles';

const useStyles = makeStyles((theme) => ({
    root: {
        padding: theme.spacing(2),
    },
    chartContainer: {
        height: 400,
        marginTop: theme.spacing(2),
    }
}));

export default function MarketTrends({ data }) {
    const classes = useStyles();

    if (!data) {
        return <div>{__('No market trend data available', 'vortex-ai-agents')}</div>;
    }

    const chartData = {
        labels: data.map(point => point.date),
        datasets: [
            {
                label: __('Market Value Index', 'vortex-ai-agents'),
                data: data.map(point => point.value),
                fill: false,
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1
            },
            {
                label: __('Transaction Volume', 'vortex-ai-agents'),
                data: data.map(point => point.volume),
                fill: false,
                borderColor: 'rgb(255, 99, 132)',
                tension: 0.1
            }
        ]
    };

    const options = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
            },
            title: {
                display: true,
                text: __('Art Market Trends', 'vortex-ai-agents')
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: __('Value', 'vortex-ai-agents')
                }
            },
            x: {
                title: {
                    display: true,
                    text: __('Date', 'vortex-ai-agents')
                }
            }
        }
    };

    return (
        <div className={classes.root}>
            <Typography variant="h6" gutterBottom>
                {__('Market Trends Analysis', 'vortex-ai-agents')}
            </Typography>
            <Box className={classes.chartContainer}>
                <Line data={chartData} options={options} />
            </Box>
            <Typography variant="body2" color="textSecondary" style={{ marginTop: 16 }}>
                {__('Data updated daily from global art market sources', 'vortex-ai-agents')}
            </Typography>
        </div>
    );
} 