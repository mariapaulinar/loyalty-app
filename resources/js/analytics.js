'use strict';

/**
 * Reward Loyalty - Proprietary Software
 * Copyright (c) 2025 NowSquare. All rights reserved.
 * See LICENSE file for terms.
 *
 * Analytics Chart System - Declarative ApexCharts Orchestration
 *
 * Philosophy:
 * "Simplicity is the ultimate sophistication" - Leonardo da Vinci
 *
 * This module transforms complex chart configuration into a single, elegant API.
 * Like Apple's design language, we hide complexity behind intuitive data attributes.
 * Like Tesla's software, we provide sensible defaults that "just work."
 *
 * Architecture:
 * - **Declarative**: HTML elements self-describe their chart configuration
 * - **Automatic**: Charts initialize on DOM load without manual wiring
 * - **Themeable**: Seamless light/dark mode with CSS variable integration
 * - **Extensible**: Add new chart types without touching existing code
 * - **Type-safe**: JSDoc annotations guide IDE autocomplete
 *
 * Usage Example:
 * <div id="sales-chart"
 *      data-chart-type="line"
 *      data-labels='["Mon","Tue","Wed"]'
 *      data-values='[100,200,150]'
 *      data-label="Revenue">
 * </div>
 *
 * The chart renders itself. No JavaScript required. That's the magic.
 */

import ApexCharts from 'apexcharts';

/**
 * Design System: Chart Color Palette
 *
 * These colors are carefully chosen for:
 * - Accessibility (WCAG AA contrast ratios)
 * - Aesthetic harmony (golden ratio spacing in HSL)
 * - Data distinction (colorblind-safe palette)
 * - Brand consistency (matches our primary theme)
 */
const CHART_COLORS = {
    primary: '#6366f1',      // Indigo-500 - Our brand color
    secondary: '#8b5cf6',    // Violet-500 - Complementary
    success: '#10b981',      // Emerald-500 - Positive metrics
    warning: '#f59e0b',      // Amber-500 - Attention items
    danger: '#ef4444',       // Red-500 - Critical alerts
    info: '#3b82f6',         // Blue-500 - Informational
    neutral: '#6b7280',      // Gray-500 - Neutral data
    
    // Extended palette for multi-series charts
    palette: [
        '#6366f1', '#8b5cf6', '#ec4899', '#f43f5e',
        '#f97316', '#f59e0b', '#84cc16', '#10b981',
        '#14b8a6', '#06b6d4', '#0ea5e9', '#3b82f6',
    ],
};

/**
 * Theme Engine: Dynamic Dark Mode Support
 *
 * Detects system/user theme preference and returns appropriate chart styling.
 * Updates automatically when theme changes (via MutationObserver in utility.js).
 *
 * @returns {Object} Theme configuration for ApexCharts
 */
function getThemeConfig() {
    const isDark = document.documentElement.classList.contains('dark');
    
    return {
        mode: isDark ? 'dark' : 'light',
        palette: 'palette1',
        
        // Typography scales with theme
        typography: {
            fontFamily: 'Geist, Inter, system-ui, -apple-system, sans-serif',
            fontSize: '13px',
            fontWeight: 400,
            colors: isDark ? ['#e5e7eb'] : ['#374151'],
        },
        
        // Grid adapts to theme luminosity
        grid: {
            borderColor: isDark ? '#374151' : '#e5e7eb',
            strokeDashArray: 4,
            xaxis: { lines: { show: false } },
            yaxis: { lines: { show: true } },
            padding: { top: 20, right: 20, bottom: 0, left: 10 },
        },
        
        // Tooltips with glassmorphism effect
        tooltip: {
            theme: isDark ? 'dark' : 'light',
            style: {
                fontSize: '13px',
                fontFamily: 'Geist, Inter, system-ui, -apple-system, sans-serif',
            },
            fillSeriesColor: false,
            marker: { show: true },
            x: { show: true },
            y: {
                title: {
                    formatter: (seriesName) => seriesName + ': ',
                },
            },
        },
        
        // Subtle animations - fast enough to feel instant, slow enough to track
        animations: {
            enabled: true,
            easing: 'easeinout',
            speed: 400,
            animateGradually: { enabled: true, delay: 150 },
            dynamicAnimation: { enabled: true, speed: 350 },
        },
    };
}

/**
 * Chart Factory: Line Chart Preset
 *
 * Perfect for: Time-series data, trends, activity timelines
 * Design: Smooth curves with gradient fill for visual depth
 *
 * @param {HTMLElement} element - Container element with data attributes
 * @returns {Object} ApexCharts configuration
 */
function createLineChartConfig(element) {
    const dataset = element.dataset;
    const labels = JSON.parse(dataset.labels || '[]');
    const values = JSON.parse(dataset.values || '[]');
    const seriesLabel = dataset.label || 'Value';
    const color = dataset.color || CHART_COLORS.primary;
    const theme = getThemeConfig();
    
    return {
        chart: {
            type: 'area',
            height: parseInt(dataset.height || '320'),
            fontFamily: theme.typography.fontFamily,
            toolbar: { show: false },
            zoom: { enabled: false },
            background: 'transparent',
            animations: theme.animations,
        },
        
        series: [{
            name: seriesLabel,
            data: values,
        }],
        
        colors: [color],
        
        // Smooth Bezier curves - visually pleasing without sacrificing accuracy
        stroke: {
            curve: 'smooth',
            width: 3,
            lineCap: 'round',
        },
        
        // Gradient fill adds depth perception
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.4,
                opacityTo: 0.05,
                stops: [0, 90, 100],
            },
        },
        
        dataLabels: {
            enabled: false,
        },
        
        grid: theme.grid,
        
        xaxis: {
            categories: labels,
            labels: {
                style: {
                    colors: theme.typography.colors,
                    fontSize: '12px',
                    fontWeight: 500,
                },
                rotate: -45,
                rotateAlways: false,
            },
            axisBorder: { show: false },
            axisTicks: { show: false },
            crosshairs: { show: true },
        },
        
        yaxis: {
            labels: {
                style: {
                    colors: theme.typography.colors,
                    fontSize: '12px',
                },
                formatter: (val) => Math.round(val).toLocaleString(),
            },
        },
        
        tooltip: {
            ...theme.tooltip,
            y: {
                formatter: (val) => val.toLocaleString(),
            },
        },
        
        legend: {
            show: false,
        },
        
        // Subtle hover effect - 1.15x scale is the golden ratio of feedback
        states: {
            hover: {
                filter: {
                    type: 'lighten',
                    value: 0.15,
                },
            },
            active: {
                filter: {
                    type: 'darken',
                    value: 0.15,
                },
            },
        },
    };
}

/**
 * Chart Factory: Bar Chart Preset
 *
 * Perfect for: Comparisons, rankings, categorical data
 * Design: Rounded corners with subtle shadows for depth
 *
 * @param {HTMLElement} element - Container element with data attributes
 * @returns {Object} ApexCharts configuration
 */
function createBarChartConfig(element) {
    const dataset = element.dataset;
    const labels = JSON.parse(dataset.labels || '[]');
    const values = JSON.parse(dataset.values || '[]');
    const seriesLabel = dataset.label || 'Value';
    // Check for single color first (data-color), then multi-color palette (data-colors)
    const singleColor = dataset.color; // Single color for all bars
    const colors = dataset.colors ? JSON.parse(dataset.colors) : (singleColor ? [singleColor] : CHART_COLORS.palette);
    const distributed = dataset.colors ? true : false; // Only distribute if explicitly using data-colors palette
    const theme = getThemeConfig();
    const horizontal = dataset.orientation === 'horizontal';
    
    return {
        chart: {
            type: 'bar',
            height: parseInt(dataset.height || '320'),
            fontFamily: theme.typography.fontFamily,
            toolbar: { show: false },
            background: 'transparent',
            animations: theme.animations,
        },
        
        series: [{
            name: seriesLabel,
            data: values,
        }],
        
        colors: colors,
        
        plotOptions: {
            bar: {
                horizontal: horizontal,
                columnWidth: '70%',
                barHeight: '70%',
                borderRadius: 8,
                borderRadiusApplication: 'end',
                distributed: distributed, // Only distribute when using palette
                dataLabels: {
                    position: 'top',
                },
            },
        },
        
        dataLabels: {
            enabled: false,
        },
        
        grid: theme.grid,
        
        xaxis: {
            categories: labels,
            labels: {
                style: {
                    colors: theme.typography.colors,
                    fontSize: '12px',
                },
                rotate: labels.length > 6 ? -45 : 0,
            },
            axisBorder: { show: false },
            axisTicks: { show: false },
        },
        
        yaxis: {
            labels: {
                style: {
                    colors: theme.typography.colors,
                    fontSize: '12px',
                },
                formatter: (val) => Math.round(val).toLocaleString(),
            },
        },
        
        tooltip: {
            ...theme.tooltip,
            y: {
                formatter: (val) => val.toLocaleString(),
            },
        },
        
        legend: {
            show: false,
        },
        
        states: {
            hover: {
                filter: {
                    type: 'darken',
                    value: 0.1,
                },
            },
        },
    };
}

/**
 * Chart Factory: Donut Chart Preset
 *
 * Perfect for: Proportions, percentages, composition breakdowns
 * Design: Modern donut with center label showing total
 *
 * @param {HTMLElement} element - Container element with data attributes
 * @returns {Object} ApexCharts configuration
 */
function createDonutChartConfig(element) {
    const dataset = element.dataset;
    const labels = JSON.parse(dataset.labels || '[]');
    const values = JSON.parse(dataset.values || '[]');
    const colors = dataset.colors ? JSON.parse(dataset.colors) : CHART_COLORS.palette;
    const theme = getThemeConfig();
    
    return {
        chart: {
            type: 'donut',
            height: parseInt(dataset.height || '320'),
            fontFamily: theme.typography.fontFamily,
            toolbar: { show: false },
            background: 'transparent',
            animations: theme.animations,
        },
        
        series: values,
        
        labels: labels,
        
        colors: colors,
        
        plotOptions: {
            pie: {
                startAngle: -90,
                endAngle: 270,
                donut: {
                    size: '70%',
                    labels: {
                        show: true,
                        name: {
                            show: true,
                            fontSize: '14px',
                            fontWeight: 600,
                            color: theme.typography.colors[0],
                        },
                        value: {
                            show: true,
                            fontSize: '28px',
                            fontWeight: 700,
                            color: theme.typography.colors[0],
                            formatter: (val) => parseInt(val).toLocaleString(),
                        },
                        total: {
                            show: true,
                            label: dataset.totalLabel || 'Total',
                            fontSize: '14px',
                            fontWeight: 600,
                            color: theme.typography.colors[0],
                            formatter: (w) => {
                                const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                return total.toLocaleString();
                            },
                        },
                    },
                },
            },
        },
        
        dataLabels: {
            enabled: false,
        },
        
        legend: {
            show: true,
            position: 'bottom',
            horizontalAlign: 'center',
            fontSize: '13px',
            fontWeight: 500,
            fontFamily: theme.typography.fontFamily,
            labels: {
                colors: theme.typography.colors[0],
            },
            markers: {
                width: 12,
                height: 12,
                radius: 12,
                offsetX: -4,
            },
            itemMargin: {
                horizontal: 12,
                vertical: 8,
            },
        },
        
        tooltip: {
            ...theme.tooltip,
            y: {
                formatter: (val) => val.toLocaleString(),
            },
        },
        
        states: {
            hover: {
                filter: {
                    type: 'lighten',
                    value: 0.1,
                },
            },
        },
        
        // Responsive breakpoints for mobile optimization
        responsive: [{
            breakpoint: 480,
            options: {
                chart: {
                    height: 280,
                },
                legend: {
                    position: 'bottom',
                },
            },
        }],
    };
}

/**
 * Chart Factory: Multi-Bar Chart Preset
 *
 * Perfect for: Comparing multiple metrics side-by-side
 * Design: Grouped bars with distinct colors per series
 *
 * @param {HTMLElement} element - Container element with data attributes
 * @returns {Object} ApexCharts configuration
 */
function createMultiBarChartConfig(element) {
    const dataset = element.dataset;
    const labels = JSON.parse(dataset.labels || '[]');
    const colors = dataset.colors ? JSON.parse(dataset.colors) : CHART_COLORS.palette.slice(0, 2);
    const theme = getThemeConfig();
    
    // Multi-series support: data-values1, data-values2, etc.
    const series = [];
    let seriesIndex = 1;
    while (dataset[`values${seriesIndex}`]) {
        const values = JSON.parse(dataset[`values${seriesIndex}`]);
        const label = dataset[`label${seriesIndex}`] || `Series ${seriesIndex}`;
        series.push({
            name: label,
            data: values,
        });
        seriesIndex++;
    }
    
    return {
        chart: {
            type: 'bar',
            height: parseInt(dataset.height || '320'),
            fontFamily: theme.typography.fontFamily,
            toolbar: { show: false },
            background: 'transparent',
            animations: theme.animations,
            stacked: dataset.stacked === 'true',
        },
        
        series: series,
        
        colors: colors,
        
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '70%',
                borderRadius: 8,
                borderRadiusApplication: 'end',
            },
        },
        
        dataLabels: {
            enabled: false,
        },
        
        grid: theme.grid,
        
        xaxis: {
            categories: labels,
            labels: {
                style: {
                    colors: theme.typography.colors,
                    fontSize: '12px',
                },
                rotate: labels.length > 6 ? -45 : 0,
            },
            axisBorder: { show: false },
            axisTicks: { show: false },
        },
        
        yaxis: {
            labels: {
                style: {
                    colors: theme.typography.colors,
                    fontSize: '12px',
                },
                formatter: (val) => Math.round(val).toLocaleString(),
            },
        },
        
        tooltip: {
            ...theme.tooltip,
            shared: true,
            intersect: false,
            y: {
                formatter: (val) => val.toLocaleString(),
            },
        },
        
        legend: {
            show: series.length > 1,
            position: 'top',
            horizontalAlign: 'right',
            fontSize: '13px',
            fontWeight: 500,
            fontFamily: theme.typography.fontFamily,
            labels: {
                colors: theme.typography.colors[0],
            },
            markers: {
                width: 12,
                height: 12,
                radius: 2,
                offsetX: -4,
            },
        },
        
        states: {
            hover: {
                filter: {
                    type: 'darken',
                    value: 0.1,
                },
            },
        },
    };
}

/**
 * Chart Registry: Type-to-Factory Mapping
 *
 * This is the Rosetta Stone - it translates data-chart-type attributes
 * into concrete chart configurations. Add new chart types here.
 */
const CHART_FACTORIES = {
    'line': createLineChartConfig,
    'area': createLineChartConfig, // Alias
    'bar': createBarChartConfig,
    'column': createBarChartConfig, // Alias
    'donut': createDonutChartConfig,
    'pie': createDonutChartConfig, // Alias
    'multi-bar': createMultiBarChartConfig,
};

/**
 * Chart Initializer: Auto-Discovery and Rendering
 *
 * Scans the DOM for chart elements, determines their type, generates configuration,
 * and renders them. This is the engine that makes the magic happen.
 *
 * Algorithm:
 * 1. Query all elements with [data-chart-type] attribute
 * 2. For each element, determine chart factory from registry
 * 3. Generate configuration by invoking factory
 * 4. Instantiate ApexCharts and render
 * 5. Store instance on element for later access (updates, destruction)
 *
 * @returns {void}
 */
function initializeCharts() {
    // Find all chart containers using the universal selector
    const chartElements = document.querySelectorAll('[data-chart-type]');
    
    if (chartElements.length === 0) {
        return; // Early exit - no charts to render
    }
    
    // Process each chart element
    chartElements.forEach((element) => {
        const chartType = element.dataset.chartType;
        
        // Validate chart type
        if (!CHART_FACTORIES[chartType]) {
            console.warn(`[Analytics] Unknown chart type: "${chartType}". Available types:`, Object.keys(CHART_FACTORIES).join(', '));
            return;
        }
        
        // Skip if already initialized (prevent double-rendering)
        if (element._apexChart) {
            return;
        }
        
        try {
            // Generate configuration using the appropriate factory
            const factory = CHART_FACTORIES[chartType];
            const config = factory(element);
            
            // Instantiate and render
            const chart = new ApexCharts(element, config);
            chart.render();
            
            // Store reference for later access (useful for updates/destruction)
            element._apexChart = chart;
            
        } catch (error) {
            console.error(`[Analytics] Failed to initialize chart #${element.id}:`, error);
        }
    });
}

/**
 * Theme Observer: Dynamic Rerendering on Theme Change
 *
 * Watches the document element for class changes. When dark mode toggles,
 * all charts are destroyed and recreated with new theme configuration.
 *
 * This ensures charts always match the active theme without page refresh.
 */
function initializeThemeObserver() {
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.attributeName === 'class') {
                const chartElements = document.querySelectorAll('[data-chart-type]');
                
                // Destroy existing charts
                chartElements.forEach((element) => {
                    if (element._apexChart) {
                        element._apexChart.destroy();
                        delete element._apexChart;
                    }
                });
                
                // Re-initialize with new theme
                initializeCharts();
            }
        });
    });
    
    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class'],
    });
}

/**
 * Global API: Programmatic Chart Management
 *
 * Exposed as window.RewardLoyaltyCharts for manual chart operations.
 * Useful for dynamic dashboards, A/B testing, or advanced customization.
 */
window.RewardLoyaltyCharts = {
    /**
     * Manually initialize charts (useful after dynamic content injection)
     */
    init: initializeCharts,
    
    /**
     * Update chart data without full rerender
     * @param {string} elementId - Chart container ID
     * @param {Array} newData - New series data
     */
    updateChart: (elementId, newData) => {
        const element = document.getElementById(elementId);
        if (element?._apexChart) {
            element._apexChart.updateSeries([{ data: newData }]);
        }
    },
    
    /**
     * Destroy a specific chart
     * @param {string} elementId - Chart container ID
     */
    destroyChart: (elementId) => {
        const element = document.getElementById(elementId);
        if (element?._apexChart) {
            element._apexChart.destroy();
            delete element._apexChart;
        }
    },
    
    /**
     * Access to color palette for custom styling
     */
    colors: CHART_COLORS,
};

/**
 * Bootstrap: Initialize on DOM Ready
 *
 * Using DOMContentLoaded ensures the DOM is fully parsed before we query it.
 * This prevents race conditions with server-side rendering or slow networks.
 */
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initializeCharts();
        initializeThemeObserver();
    });
} else {
    // DOM already loaded (hot module replacement or late script execution)
    initializeCharts();
    initializeThemeObserver();
}

/**
 * Legacy Support: Backward compatibility with old double bar chart function
 * @deprecated Use data attributes instead
 */
function createDoubleBarChart(elementId) {
    console.warn('[Analytics] createDoubleBarChart is deprecated. Use data-chart-type="multi-bar" instead.');
    const element = document.getElementById(elementId);
    if (element && !element.dataset.chartType) {
        element.dataset.chartType = 'multi-bar';
        initializeCharts();
    }
}

// Export for potential ES module usage
export { initializeCharts, CHART_COLORS, getThemeConfig };
