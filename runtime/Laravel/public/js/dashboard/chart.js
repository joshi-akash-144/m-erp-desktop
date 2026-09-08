document.addEventListener("DOMContentLoaded", function () {
    const initialization = () => {
        // Helper to get tabler colors
        const getColor = (colorName) => {
            const colors = {
                'primary': '#066fd1',
                'red': '#d63939',
                'green': '#2fb344',
                'yellow': '#f59f00',
                'orange': '#f76707',
                'azure': '#4299e1',
                'purple': '#7c5cfc',
                'indigo': '#4263eb',
                'gray-600': '#72767d'
            };
            try {
                return window.tabler?.getColor?.(colorName) || colors[colorName] || colorName;
            } catch (e) {
                return colors[colorName] || colorName;
            }
        };

        // const updateGrowthUI = (prefix, value) => {
        //     const $container = $(`#stat-${prefix}-growth-container, #stat-${prefix}-growth-alt-container`);
        //     const $valueEl = $(`#stat-${prefix}-growth-abs, #stat-${prefix}-growth-perc`);
        //     const $path1 = $(`#stat-${prefix}-growth-path-1, #stat-${prefix}-growth-alt-path-1`);
        //     const $path2 = $(`#stat-${prefix}-growth-path-2, #stat-${prefix}-growth-alt-path-2`);

        //     if (!$container.length) return;

        //     const isPositive = value >= 0;
            
        //     // Update Color
        //     $container.removeClass('text-green text-red').addClass(isPositive ? 'text-green' : 'text-red');

        //     // Update Value
        //     if ($valueEl.length) $valueEl.text(Math.abs(value));

        //     // Update Icons
        //     if ($path1.length) $path1.attr('d', isPositive ? 'M3 17l6 -6l4 4l8 -8' : 'M3 7l6 6l4 -4l8 8');
        //     if ($path2.length) $path2.attr('d', isPositive ? 'M14 7l7 0l0 7' : 'M21 10l0 7l-7 0');
        // };

        const charts = {};

        const getChartline = (name, data, color, type = 'area') => ({
            chart: {
                type: type,
                height: 60,
                sparkline: { enabled: true },
                animations: { enabled: true, easing: 'easeinout', speed: 800 },
            },
            fill: { 
                opacity: .15, 
                type: 'gradient',
                gradient: { shadeIntensity: 1, opacityFrom: 0.5, opacityTo: 0.1, stops: [0, 100] }
            },
            stroke: { width: 3, curve: "smooth", lineCap: "round" },
            series: [{ name: name, data: data }],
            tooltip: {
                theme: 'dark',
                fixed: { enabled: false },
                x: { show: false }, 
                y: { 
                    title: { formatter: () => name },
                    formatter: (val) => val.toLocaleString()
                },
                marker: { show: false }
            },
            yaxis: { min: 0, padding: { top: 10, bottom: 10 } },
            xaxis: { type: 'category' },
            labels: [],
            colors: [getColor(color)],
        });

        // Initialize all charts with empty data
        const initCharts = () => {
            // 1. Total Users
            if (document.getElementById('chart-visitors')) {
                charts.visitors = new ApexCharts(document.getElementById('chart-visitors'), 
                    getChartline("Total Users", [], "primary")
                );
                charts.visitors.render();
            }

            // 2. Active Users Radial
            if (document.getElementById('chart-conversion-radial')) {
                charts.activeUsers = new ApexCharts(document.getElementById('chart-conversion-radial'), {
                    chart: { type: 'radialBar', height: 230, sparkline: { enabled: true } },
                    plotOptions: {
                        radialBar: {
                            startAngle: -120, endAngle: 120, hollow: { size: '70%' },
                            track: { strokeWidth: '100%', margin: 3 },
                            dataLabels: {
                                show: true, name: { show: false },
                                value: { offsetY: 10, fontSize: '32px', fontWeight: 'bold', formatter: (val) => val + '%' }
                            }
                        }
                    },
                    colors: [getColor('primary')],
                    series: [0],
                    stroke: { lineCap: 'round' }
                });
                charts.activeUsers.render();
            }

            // 3. Revenue
            if (document.getElementById('chart-revenue-bg')) {
                charts.revenue = new ApexCharts(document.getElementById('chart-revenue-bg'), 
                    getChartline("Revenue", [], "green")
                );
                charts.revenue.render();
            }

            // 4. Purchase
            if (document.getElementById('chart-purchase-revenue-bg')) {
                charts.purchase = new ApexCharts(document.getElementById('chart-purchase-revenue-bg'), 
                    getChartline("Purchases", [], "orange")
                );
                charts.purchase.render();
            }

            // 5. GRN
            if (document.getElementById('chart-grn-revenue-bg')) {
                charts.grn = new ApexCharts(document.getElementById('chart-grn-revenue-bg'), 
                    getChartline("GRN Value", [], "azure")
                );
                charts.grn.render();
            }

            // 6. Monthly Overview
            if (document.getElementById('sales-purchase-overview')) {
                charts.overview = new ApexCharts(document.getElementById('sales-purchase-overview'), {
                    chart: { 
                        type: 'bar',
                        height: 350,
                        fontFamily: 'inherit',
                        toolbar: { show: false } 
                    },
                    plotOptions: { 
                        bar: { 
                            horizontal: false, 
                            columnWidth: '55%', 
                            borderRadius: 0 
                        } 
                    },
                    dataLabels: { 
                        enabled: false 
                    },
                    series: [
                        { name: 'Purchase', data: [] },
                        { name: 'Sales', data: [] }
                    ],
                    xaxis: { categories: [] },
                    yaxis: { 
                        labels: { 
                            formatter: formatValue 
                        } 
                    },
                    tooltip: { 
                        theme: 'dark', 
                        y: { formatter: formatValue } 
                    },
                    colors: [getColor('primary'), getColor('orange')],
                    legend: { 
                        show: true,
                        position: 'top',
                        horizontalAlign: 'center',
                        offsetY: 0,
                        itemMargin: {
                            horizontal: 10,
                            vertical: 5
                        }
                    },
                    grid: { 
                        strokeDashArray: 4 
                    }
                });
                charts.overview.render();
            }

            // 7. Dairy Outstanding
            if (document.getElementById('dairy-outstanding')) {
                charts.dairy = new ApexCharts(document.getElementById('dairy-outstanding'), {
                    chart: { 
                        type: 'donut', 
                        fontFamily: 'inherit', 
                        height: 350, 
                        sparkline: { enabled: false } 
                    },
                    // series: [1],
                    tooltip: { theme: 'dark', y: { formatter: formatValue } },
                    colors: [getColor('primary'), getColor('azure'), getColor('indigo'), getColor('gray-600')],
                    legend: { show: true, position: 'bottom', offsetY: 12 },
                });
                charts.dairy.render();
            }

            // 8. Profitability Trend
            if (document.getElementById('profitability-trend')) {
                charts.profitability = new ApexCharts(document.getElementById('profitability-trend'), {
                    chart: { type: 'line', height: 320, fontFamily: 'inherit', toolbar: { show: false } },
                    stroke: { width: [4, 4], curve: 'smooth' },
                    series: [
                        { name: 'Revenue', data: [] },
                        { name: 'Expense', data: [] }
                    ],
                    xaxis: { categories: [] },
                    yaxis: { labels: { formatter: formatValue } },
                    tooltip: { theme: 'dark', y: { formatter: formatValue } },
                    colors: [getColor('primary'), getColor('orange')],
                    legend: { position: 'top', horizontalAlign: 'center', offsetY: -10 },
                    grid: { strokeDashArray: 4 }
                });
                charts.profitability.render();
            }

            // 9. Cash Flow
            if (document.getElementById('cash-flow-preview')) {
                charts.cashFlow = new ApexCharts(document.getElementById('cash-flow-preview'), {
                    chart: { type: 'bar', height: 320, fontFamily: 'inherit', toolbar: { show: false } },
                    plotOptions: { bar: { distributed: true, borderRadius: 4, columnWidth: '60%' } },
                    series: [{ name: 'Amount', data: [] }],
                    xaxis: { categories: [] },
                    yaxis: { labels: { formatter: formatValue } },
                    tooltip: { theme: 'dark', y: { formatter: formatValue } },
                    colors: [getColor('green'), getColor('red')],
                    legend: { show: false },
                    grid: { strokeDashArray: 4 }
                });
                charts.cashFlow.render();
            }
        };

        const updateDashboardData = (days, filter = null) => {
            const loader = document.getElementById('dashboard-loader');
            
            // Only show full screen loader on initial load (no metric specified)
            if (!filter && loader) {
                loader.classList.remove('d-none');
                loader.classList.add('d-flex');
            }

            const url = filter 
                ? `${filterRoute}?days=${days}&filter=${filter}`
                : `${filterRoute}?days=${days}`;

            fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                // HELPER: Update individual filter stats
                const updateFilterUI = (type, d) => {
                    if (type === 'users') {
                        if (document.getElementById('stat-total-users')) document.getElementById('stat-total-users').innerText = formatValue(d.totalUsers);
                        if (document.getElementById('stat-active-users')) document.getElementById('stat-active-users').innerText = formatValue(d.activeUsers);
                        if (d.growth) {
                            // updateGrowthUI('new-clients', d.growth.newClients);
                            if (document.getElementById('stat-total-users-diff')) document.getElementById('stat-total-users-diff').innerText = d.growth.totalUsersDiff || 0;
                        }
                        if (charts.visitors) {
                            charts.visitors.updateSeries([{ data: d.totalUsersTrend?.map(i => i.count) || [] }]);
                            charts.visitors.updateOptions({ labels: d.labels || [] });
                        }
                        if (charts.activeUsers) charts.activeUsers.updateSeries([Math.min(100, d.activeUserPercentage || 0)]);
                    }

                    if (type === 'sales') {
                        if (document.getElementById('stat-today-sales')) document.getElementById('stat-today-sales').innerText = formatValue(d.todaySales);
                        if (document.getElementById('stat-total-revenue')) document.getElementById('stat-total-revenue').innerText = formatCurrency(d.totalRevenue);
                        if (d.growth) {
                            // updateGrowthUI('sales', d.growth.sales);
                            if (document.getElementById('stat-sales-growth-perc')) document.getElementById('stat-sales-growth-perc').innerText = (d.growth.sales || 0) + '%';
                            if (document.getElementById('stat-sales-growth-alt')) {
                                const val = (d.growth.sales >= 0 ? '+' : '') + d.growth.sales + '%';
                                document.getElementById('stat-sales-growth-alt').innerText = val;
                            }
                        }
                        if (charts.revenue) {
                            charts.revenue.updateSeries([{ data: d.salesData?.map(i => i.amount) || [] }]);
                            charts.revenue.updateOptions({ labels: d.labels || [] });
                        }
                    }

                    if (type === 'purchase') {
                        if (document.getElementById('stat-total-expense')) document.getElementById('stat-total-expense').innerText = formatCurrency(d.totalExpense);
                        // if (d.growth) updateGrowthUI('purchase', d.growth.purchase);
                        if (charts.purchase) {
                            charts.purchase.updateSeries([{ data: d.purchaseData?.map(i => i.amount) || [] }]);
                            charts.purchase.updateOptions({ labels: d.labels || [] });
                        }
                    }

                    if (type === 'grn') {
                        if (document.getElementById('stat-total-grn-revenue')) document.getElementById('stat-total-grn-revenue').innerText = formatCurrency(d.totalGrnRevenue);
                        // if (d.growth) updateGrowthUI('grn', d.growth.grn);
                        if (charts.grn) {
                            charts.grn.updateSeries([{ data: d.grnData?.map(i => i.amount) || [] }]);
                            charts.grn.updateOptions({ labels: d.labels || [] });
                        }
                    }
                    
                    if (type === 'godown') {
                        if (d.godown) {
                            if (document.getElementById('stat-godown-product-in')) document.getElementById('stat-godown-product-in').innerText = formatValue(d.godown.product_in);
                            if (document.getElementById('stat-godown-product-out')) document.getElementById('stat-godown-product-out').innerText = formatValue(d.godown.product_out);
                            if (document.getElementById('stat-godown-pending-in')) document.getElementById('stat-godown-pending-in').innerText = formatValue(d.godown.pending_in);
                            if (document.getElementById('stat-godown-pending-out')) document.getElementById('stat-godown-pending-out').innerText = formatValue(d.godown.pending_out);
                        }
                    }
                };

                if (filter) {
                    // Targeted Update
                    updateFilterUI(filter, data);
                    
                    // Handle Overview Specific Update
                    if (filter === 'overview') {
                        if (charts.overview) {
                            charts.overview.updateSeries([
                                { name: 'Purchase', data: data.monthlyOverview?.map(i => i.purchase) || [] },
                                { name: 'Sales', data: data.monthlyOverview?.map(i => i.sales) || [] }
                            ]);
                            charts.overview.updateOptions({ xaxis: { categories: data.monthlyOverview?.map(i => i.month) || [] } });
                        }
                        const skeleton = document.getElementById('overview-skeleton');
                        const target = document.getElementById('sales-purchase-overview');
                        if (skeleton) skeleton.classList.add('d-none');
                        if (target) target.classList.remove('d-none');
                    }

                    // Handle Dairy Specific Update
                    if (filter === 'dairy') {
                        if (charts.dairy) {
                            charts.dairy.updateSeries(data.outstandingOverview?.length ? data.outstandingOverview.map(i => Math.abs(i.value)) : [0]);
                            charts.dairy.updateOptions({ labels: data.outstandingOverview?.length ? data.outstandingOverview.map(i => i.label) : ["No Data"] });
                        }
                        const skeleton = document.getElementById('dairy-skeleton');
                        const target = document.getElementById('dairy-outstanding');
                        setTimeout(() => {
                            if (skeleton) skeleton.classList.add('d-none');
                            if (target) target.classList.remove('d-none');
                        }, 100);
                    }

                    // Update only the targeted dropdown label + data-days attribute
                    const dropdownId = filter === 'users' ? 'users' : (filter === 'sales' ? 'revenue' : filter);
                    const dropdownToggle = document.getElementById(`${dropdownId}-dropdown`);
                    if (dropdownToggle) {
                        dropdownToggle.setAttribute('data-days', days);
                        dropdownToggle.innerText = days === 'all' ? 'All' : (days === 'today' ? 'Today' : (days == 90 ? 'Last 3 months' : `Last ${days} days`));
                    }
                } else {
                    // Full Dashboard Update (Initial Load - Only Stats & Sparklines)
                    updateFilterUI('users', data);
                    updateFilterUI('sales', data);
                    updateFilterUI('purchase', data);
                    updateFilterUI('grn', data);
                    updateFilterUI('godown', data);

                    if (charts.profitability) {
                        charts.profitability.updateSeries([
                            { name: 'Revenue', data: data.profitabilityTrend?.map(i => i.revenue) || [] },
                            { name: 'Expense', data: data.profitabilityTrend?.map(i => i.expense) || [] }
                        ]);
                        charts.profitability.updateOptions({ xaxis: { categories: data.profitabilityTrend?.map(i => i.month) || [] } });
                    }

                    if (charts.cashFlow) {
                        charts.cashFlow.updateSeries([{ name: 'Amount', data: data.cashFlowPreview?.map(i => i.value) || [] }]);
                        charts.cashFlow.updateOptions({ xaxis: { categories: data.cashFlowPreview?.map(i => i.label) || [] } });
                    }

                    // Update all dropdown labels
                    document.querySelectorAll('.dropdown-toggle[id$="-dropdown"]').forEach(el => {
                        el.setAttribute('data-days', days);
                        el.innerText = days === 'all' ? 'All' : (days === 'today' ? 'Today' : (days == 90 ? 'Last 3 months' : `Last ${days} days`));
                    });
                }
            })
            .finally(() => {
                if (loader) {
                    loader.classList.add('d-none');
                    loader.classList.remove('d-flex');
                }
            });
        };

        // Event Listeners for Filters
        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('filter-days')) {
                e.preventDefault();
                const days = e.target.getAttribute('data-days');
                const filter = e.target.getAttribute('data-filter');
                
                // Update active state in local dropdown only
                const parentDropdown = e.target.closest('.dropdown-menu');
                if (parentDropdown) {
                    parentDropdown.querySelectorAll('.filter-days').forEach(el => el.classList.remove('active'));
                    e.target.classList.add('active');
                }

                updateDashboardData(days, filter);
            }
        });

        initCharts();

        // Trigger initial AJAX loads independently
        // Use Blade-passed value (default: 'today'); fallback only if completely missing
        const initialDays = (window.dashboardSettings?.initialDays !== undefined && window.dashboardSettings.initialDays !== '')
            ? window.dashboardSettings.initialDays
            : 'today';
        
        // 1. Top Metrics & Trends (Sparklines)
        updateDashboardData(initialDays);
        
        // 2. Overview Chart (Independent)
        updateDashboardData(initialDays, 'overview');
        
        // 3. Dairy Chart (Independent)
        updateDashboardData(initialDays, 'dairy');

        // 4. Godown Counts (Independent — ensures godown cards load with default filter)
        updateDashboardData(initialDays, 'godown');
    };

    initialization();
});
