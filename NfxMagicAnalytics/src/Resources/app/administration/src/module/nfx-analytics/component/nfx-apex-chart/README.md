# NfxApexChart Component

A Vue component wrapper for ApexCharts with real-time data streaming capabilities and interactive features for Shopware 6 administration.

## Features

- **Multiple Chart Types**: Area, Candlestick, Heatmap, and Radial Bar charts
- **Real-time Updates**: WebSocket simulation with smooth data transitions
- **Interactive Elements**: Tooltips, zoom, pan, and export functionality
- **Responsive Design**: Adapts to different screen sizes
- **Dark Mode Support**: Automatic theme switching
- **Performance Optimized**: Efficient data updates and animations

## Installation

1. Install dependencies from the administration root:
```bash
cd src/Resources/app/administration
npm install
```

2. Import the component in your module:
```javascript
import './component/nfx-apex-chart';
```

## Basic Usage

### Area Chart
```vue
<nfx-apex-chart
    chart-type="area"
    :series="[{
        name: 'Sales',
        data: salesData
    }]"
    :chart-options="{
        title: { text: 'Sales Overview' },
        colors: ['#189EFF']
    }"
    height="400"
/>
```

### Candlestick Chart
```vue
<nfx-apex-chart
    chart-type="candlestick"
    :series="[{
        name: 'Price',
        data: priceData
    }]"
    height="450"
/>
```

### Heatmap
```vue
<nfx-apex-chart
    chart-type="heatmap"
    :series="heatmapData"
    :chart-options="{
        xaxis: {
            categories: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri']
        }
    }"
/>
```

### Radial Bar
```vue
<nfx-apex-chart
    chart-type="radialBar"
    :series="[75, 60, 45, 90]"
    :chart-options="{
        labels: ['Q1', 'Q2', 'Q3', 'Q4']
    }"
/>
```

## Real-time Updates

Enable real-time updates with WebSocket simulation:

```vue
<nfx-apex-chart
    chart-type="area"
    :series="series"
    :real-time-enabled="true"
    :update-interval="3000"
/>
```

## Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `chartType` | String | Required | Type of chart: 'area', 'candlestick', 'heatmap', 'radialBar' |
| `series` | Array | Required | Chart data series |
| `chartOptions` | Object | `{}` | ApexCharts configuration options |
| `height` | String | `'350'` | Chart height in pixels |
| `realTimeEnabled` | Boolean | `false` | Enable real-time data updates |
| `updateInterval` | Number | `2000` | Update interval in milliseconds |
| `animationEnabled` | Boolean | `true` | Enable chart animations |

## Slots

- `header`: Content to display above the chart
- `footer`: Content to display below the chart

## Events

The component automatically handles data updates through props. For real-time updates, it simulates WebSocket connections and streams data at the specified interval.

## Styling

The component includes comprehensive theming through the `_apex-charts-theme.scss` file. Colors and styles automatically adapt to Shopware's admin theme.

## Examples

See `example-usage.js` for complete implementation examples including:
- Revenue analytics with real-time updates
- Product price analysis with candlestick charts
- Category performance heatmaps
- Conversion metrics with radial bars
- Multi-chart dashboards

## Performance Considerations

- Data points are limited to 50 for real-time charts to maintain performance
- Animations can be disabled for better performance on slower devices
- Chart updates are debounced to prevent excessive re-renders

## Browser Support

Supports all modern browsers that are compatible with Shopware 6:
- Chrome/Edge (latest)
- Firefox (latest)
- Safari (latest)