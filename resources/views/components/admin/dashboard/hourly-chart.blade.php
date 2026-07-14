@props(['data' => [], 'labels' => [], 'peakHour' => '--:--', 'title' => 'Hourly Volume', 'subtitle' => 'Sales trends throughout the day', 'peakLabel' => 'Peak Hour'])

<div class="bg-card rounded-3xl p-8 border border-border/50 shadow-sm col-span-1 lg:col-span-3 transition-all">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-lg font-extrabold text-foreground tracking-tight">{{ $title }}</h2>
            <p class="text-xs text-muted-foreground font-bold uppercase tracking-wider mt-1">{{ $subtitle }}</p>
        </div>
        <div class="flex items-center gap-4">
            <div class="px-3 py-1.5 rounded-lg bg-primary/10 border border-primary/20">
                <span class="text-[10px] font-bold text-primary uppercase tracking-widest">{{ $peakLabel }}: {{ $peakHour }}</span>
            </div>
        </div>
    </div>

    <div wire:ignore 
         x-data="{
            data: @entangle('hourlySales'),
            labels: @entangle('chartLabels'),
            chart: null,
            init() {
                const isDark = document.documentElement.classList.contains('dark');
                const labelColor = isDark ? '#94a3b8' : '#64748b';
                
                this.chart = new ApexCharts($refs.container, {
                    series: [{
                        name: 'Sales',
                        data: this.data
                    }],
                    chart: {
                        type: 'bar',
                        height: 300,
                        toolbar: { show: false },
                        animations: { enabled: true, easing: 'easeinout', speed: 800 },
                        background: 'transparent',
                        foreColor: labelColor
                    },
                    theme: {
                        mode: isDark ? 'dark' : 'light'
                    },
                    plotOptions: {
                        bar: {
                            borderRadius: 6,
                            columnWidth: '50%',
                            distributed: true
                        }
                    },
                    colors: isDark ? ['#6366f1', '#818cf8', '#4f46e5', '#4338ca', '#3730a3'] : ['#4f46e5', '#6366f1', '#818cf8', '#a5b4fc', '#c7d2fe'],
                    dataLabels: { enabled: false },
                    legend: { show: false },
                    xaxis: {
                        categories: this.labels,
                        axisBorder: { show: false },
                        axisTicks: { show: false },
                        labels: {
                            style: { colors: labelColor, fontSize: '10px', fontWeight: 700 }
                        }
                    },
                    yaxis: {
                        labels: {
                            style: { colors: labelColor, fontSize: '10px', fontWeight: 700 },
                            formatter: (val) => '₹' + Math.round(val).toLocaleString()
                        }
                    },
                    grid: {
                        borderColor: isDark ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.05)',
                        strokeDashArray: 4,
                        padding: { left: 0, right: 0 }
                    },
                    tooltip: {
                        theme: isDark ? 'dark' : 'light',
                        y: {
                            formatter: (val) => '₹' + val.toLocaleString()
                        }
                    }
                });
                this.chart.render();
                
                this.$watch('data', (value) => {
                    this.chart.updateSeries([{ data: value }]);
                });

                this.$watch('labels', (value) => {
                    this.chart.updateOptions({
                        xaxis: { categories: value }
                    });
                });

                window.addEventListener('theme-updated', () => {
                    if (this.chart) {
                        this.chart.destroy();
                        this.init();
                    }
                });
            }
         }" x-ref="container">
    </div>
</div>
