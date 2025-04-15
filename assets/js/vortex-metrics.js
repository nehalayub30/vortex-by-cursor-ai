/**
 * VORTEX Metrics Dashboard JavaScript
 */
(function($) {
    'use strict';

    // DOM ready
    $(document).ready(function() {
        initMetricsDashboard();
    });

    /**
     * Initialize metrics dashboard
     */
    function initMetricsDashboard() {
        initTabs();
        initCharts();
    }

    /**
     * Initialize tab navigation
     */
    function initTabs() {
        $('.vortex-tab-navigation a').on('click', function(e) {
            e.preventDefault();
            
            const tabId = $(this).attr('href').substring(1);
            
            $('.vortex-tab-navigation li').removeClass('active');
            $(this).parent().addClass('active');
            
            $('.vortex-tab-content').removeClass('active').hide();
            $('#' + tabId).addClass('active').fadeIn();
        });
    }

    /**
     * Initialize charts
     */
    function initCharts() {
        // Only proceed if Chart.js is loaded
        if (typeof Chart === 'undefined') {
            console.warn('Chart.js is not loaded. Charts will not be rendered.');
            return;
        }

        // Set default chart options
        Chart.defaults.font.family = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif';
        Chart.defaults.color = '#718096';
        Chart.defaults.plugins.tooltip.backgroundColor = '#2d3748';
        Chart.defaults.plugins.tooltip.padding = 10;
        Chart.defaults.plugins.tooltip.cornerRadius = 4;
        Chart.defaults.plugins.tooltip.titleFont.size = 14;
        Chart.defaults.plugins.tooltip.bodyFont.size = 13;
        Chart.defaults.elements.line.borderWidth = 3;
        Chart.defaults.elements.line.tension = 0.3;
        Chart.defaults.elements.point.radius = 4;
        Chart.defaults.elements.point.hoverRadius = 6;
        
        // Initialize charts
        initSalesChart();
        initCategoriesChart();
        initTransactionChart();
        initBlockchainChart();
    }

    /**
     * Initialize sales chart
     */
    function initSalesChart() {
        const canvas = document.getElementById('vortex-sales-chart');
        if (!canvas) return;

        // Example data - in production, this would be loaded from the server
        const labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const salesData = [15, 22, 38, 42, 65, 71, 58, 43, 39, 54, 68, 82];
        const volumeData = [3.2, 5.8, 9.4, 12.1, 19.3, 21.4, 17.6, 13.5, 11.8, 16.2, 20.5, 24.7];
        
        const ctx = canvas.getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Sales Count',
                        data: salesData,
                        borderColor: '#3182ce',
                        backgroundColor: 'rgba(49, 130, 206, 0.1)',
                        yAxisID: 'y',
                        fill: true
                    },
                    {
                        label: 'Volume (ETH)',
                        data: volumeData,
                        borderColor: '#9f7aea',
                        backgroundColor: 'rgba(159, 122, 234, 0)',
                        yAxisID: 'y1',
                        fill: false
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Sales Count'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Volume (ETH)'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Month'
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: false
                    }
                }
            }
        });
    }

    /**
     * Initialize categories chart
     */
    function initCategoriesChart() {
        const canvas = document.getElementById('vortex-categories-chart');
        if (!canvas) return;

        // Example data - in production, this would be loaded from the server
        const labels = ['Digital Art', 'Photography', 'Illustration', '3D Art', 'Pixel Art', 'Animation', 'Other'];
        const data = [35, 25, 15, 10, 8, 5, 2];
        const colors = [
            '#4299e1', '#48bb78', '#ed8936', '#9f7aea', '#f687b3', '#ecc94b', '#cbd5e0'
        ];
        
        const ctx = canvas.getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [
                    {
                        data: data,
                        backgroundColor: colors,
                        borderColor: '#ffffff',
                        borderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const percentage = Math.round((value / context.dataset.data.reduce((a, b) => a + b, 0)) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    /**
     * Initialize transaction chart
     */
    function initTransactionChart() {
        const canvas = document.getElementById('vortex-transaction-chart');
        if (!canvas) return;

        // Example data - in production, this would be loaded from the server
        const labels = ['1 Jul', '2 Jul', '3 Jul', '4 Jul', '5 Jul', '6 Jul', '7 Jul', '8 Jul', '9 Jul', '10 Jul', '11 Jul', '12 Jul', '13 Jul', '14 Jul'];
        const countData = [5, 7, 3, 8, 10, 12, 15, 9, 7, 11, 13, 8, 6, 10];
        const volumeData = [1.2, 2.1, 0.8, 3.5, 4.2, 5.6, 7.8, 4.2, 3.1, 5.5, 6.2, 4.0, 2.9, 5.1];
        
        const ctx = canvas.getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Transaction Count',
                        data: countData,
                        backgroundColor: 'rgba(49, 130, 206, 0.7)',
                        borderColor: 'rgba(49, 130, 206, 1)',
                        borderWidth: 1,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Volume (ETH)',
                        data: volumeData,
                        backgroundColor: 'rgba(159, 122, 234, 0.7)',
                        borderColor: 'rgba(159, 122, 234, 1)',
                        borderWidth: 1,
                        yAxisID: 'y1',
                        type: 'line'
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Transaction Count'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Volume (ETH)'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: false
                    }
                }
            }
        });
    }

    /**
     * Initialize blockchain chart
     */
    function initBlockchainChart() {
        const canvas = document.getElementById('vortex-blockchain-chart');
        if (!canvas) return;

        // Example data - in production, this would be loaded from the server
        const labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const nftData = [120, 185, 275, 350, 480, 620, 780, 950, 1100, 1250, 1400, 1550];
        const ownerData = [45, 72, 110, 135, 180, 210, 240, 280, 310, 340, 370, 400];
        
        const ctx = canvas.getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Total NFTs',
                        data: nftData,
                        borderColor: '#3182ce',
                        backgroundColor: 'rgba(49, 130, 206, 0.1)',
                        yAxisID: 'y',
                        fill: true
                    },
                    {
                        label: 'Unique Owners',
                        data: ownerData,
                        borderColor: '#ed8936',
                        backgroundColor: 'rgba(237, 137, 54, 0.1)',
                        yAxisID: 'y1',
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Total NFTs'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Unique Owners'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Month'
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: false
                    }
                }
            }
        });
    }

    // Utility functions
    function formatNumber(num) {
        return num.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,');
    }

    function formatCurrency(num) {
        return parseFloat(num).toFixed(2);
    }

})(jQuery); 