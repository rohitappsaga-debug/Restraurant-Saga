<div class="space-y-8" x-data="{ 
    datePreset: @entangle('datePreset')
}">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 px-2">
        <div>
            <h1 class="text-4xl font-extrabold text-foreground tracking-tight">Sales Reports</h1>
            <p class="text-muted-foreground mt-1 text-lg font-medium">Analyze sales performance and trends</p>
        </div>

        <div class="flex items-center gap-3">
            <div class="flex bg-card border border-border/50 p-1.5 rounded-2xl shadow-sm">
                @foreach(['today' => 'Today', '7d' => 'Last 7 Days', '30d' => 'Last 30 Days'] as $key => $label)
                    <button 
                        wire:click="setPreset('{{ $key }}')"
                        class="px-5 py-2.5 rounded-xl text-xs font-bold transition-all {{ $datePreset === $key ? 'bg-primary text-white shadow-lg shadow-primary/20' : 'text-muted-foreground hover:bg-muted' }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <button aria-label="Download report" class="size-12 flex items-center justify-center bg-card border border-border/50 text-muted-foreground hover:text-primary transition-all rounded-2xl shadow-sm group">
                <i data-lucide="download" class="size-5 group-hover:scale-110 transition-transform"></i>
            </button>
        </div>
    </div>

    <!-- Metrics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 px-2">
        <!-- Total Revenue -->
        <div class="p-8 bg-card border border-border/50 rounded-[2.5rem] shadow-sm relative overflow-hidden group">
            <div class="flex items-center gap-5 relative z-10">
                <div class="size-16 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-500">
                    <span class="text-2xl font-bold">{{ $settings['currency'] }}</span>
                </div>
                <div>
                    <p class="text-[10px] font-black text-muted-foreground uppercase tracking-widest leading-none mb-2">Total Revenue</p>
                    <h3 class="text-3xl font-black text-foreground">{{ $settings['currency'] }}{{ number_format($summary['revenue']['value'], 2) }}</h3>
                    <div class="mt-2 flex items-center gap-1.5 text-[10px] font-bold {{ $summary['revenue']['change'] >= 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-700 dark:text-rose-400' }}">
                        <i data-lucide="{{ $summary['revenue']['change'] >= 0 ? 'trending-up' : 'trending-down' }}" class="size-3"></i>
                        {{ $summary['revenue']['change'] >= 0 ? '+' : '' }}{{ round($summary['revenue']['change'], 1) }}% from last period
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Orders -->
        <div class="p-8 bg-card border border-border/50 rounded-[2.5rem] shadow-sm relative overflow-hidden group">
            <div class="flex items-center gap-5 relative z-10">
                <div class="size-16 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-500">
                    <i data-lucide="shopping-bag" class="size-8"></i>
                </div>
                <div>
                    <p class="text-[10px] font-black text-muted-foreground uppercase tracking-widest leading-none mb-2">Total Orders</p>
                    <h3 class="text-3xl font-black text-foreground">{{ number_format($summary['orders']['value']) }}</h3>
                    <div class="mt-2 flex items-center gap-1.5 text-[10px] font-bold {{ $summary['orders']['change'] >= 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-700 dark:text-rose-400' }}">
                        <i data-lucide="{{ $summary['orders']['change'] >= 0 ? 'trending-up' : 'trending-down' }}" class="size-3"></i>
                        {{ $summary['orders']['change'] >= 0 ? '+' : '' }}{{ round($summary['orders']['change'], 1) }}% from last period
                    </div>
                </div>
            </div>
        </div>

        <!-- Avg Order Value -->
        <div class="p-8 bg-card border border-border/50 rounded-[2.5rem] shadow-sm relative overflow-hidden group">
            <div class="flex items-center gap-5 relative z-10">
                <div class="size-16 rounded-2xl bg-orange-500/10 border border-orange-500/20 flex items-center justify-center text-orange-500">
                    <i data-lucide="wallet" class="size-8"></i>
                </div>
                <div>
                    <p class="text-[10px] font-black text-muted-foreground uppercase tracking-widest leading-none mb-2">Avg Order Value</p>
                    <h3 class="text-3xl font-black text-foreground">{{ $settings['currency'] }}{{ number_format($summary['avgValue']['value'], 2) }}</h3>
                    <div class="mt-2 flex items-center gap-1.5 text-[10px] font-bold {{ $summary['avgValue']['change'] >= 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-700 dark:text-rose-400' }}">
                        <i data-lucide="{{ $summary['avgValue']['change'] >= 0 ? 'trending-up' : 'trending-down' }}" class="size-3"></i>
                        {{ $summary['avgValue']['change'] >= 0 ? '+' : '' }}{{ round($summary['avgValue']['change'], 1) }}% from last period
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 px-2">
        <!-- Sales Trend Line Chart -->
        <div class="xl:col-span-2 bg-card border border-border/50 rounded-[3rem] p-8 shadow-sm">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h3 class="text-xl font-bold text-foreground">Sales Trend</h3>
                    <p class="text-xs text-muted-foreground mt-1">Daily revenue distribution for selected range</p>
                </div>
                <div class="px-4 py-2 bg-muted/30 rounded-xl text-[10px] font-black text-muted-foreground uppercase tracking-widest">
                    Live Performance
                </div>
            </div>
            <div id="salesTrendChart" class="h-80" wire:ignore></div>
        </div>

        <!-- Category Distribution Pie Chart -->
        <div class="bg-card border border-border/50 rounded-[3rem] p-8 shadow-sm">
            <div class="mb-8">
                <h3 class="text-xl font-bold text-foreground">Sales by Category</h3>
                <p class="text-xs text-muted-foreground mt-1">Revenue share per department</p>
            </div>
            <div id="categoryChart" class="h-80" wire:ignore></div>
        </div>
    </div>

    <!-- Analysis Tables -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 px-2 pb-12">
        <!-- Top Selling Items -->
        <div class="lg:col-span-2 bg-card border border-border/50 rounded-[3rem] shadow-sm overflow-hidden flex flex-col">
            <div class="p-8 border-b border-border/40 bg-muted/10 flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-bold text-foreground">Top Selling Items</h3>
                    <p class="text-xs text-muted-foreground mt-1">Highest grossing resources</p>
                </div>
                <i data-lucide="award" class="size-6 text-amber-500"></i>
            </div>
            <div class="flex-1 overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-muted/5">
                            <th class="text-left py-5 px-8 text-[10px] font-black text-muted-foreground uppercase tracking-[0.2em] border-b border-border/20">Rank</th>
                            <th class="text-left py-5 px-8 text-[10px] font-black text-muted-foreground uppercase tracking-[0.2em] border-b border-border/20">Item</th>
                            <th class="text-left py-5 px-8 text-[10px] font-black text-muted-foreground uppercase tracking-[0.2em] border-b border-border/20">Category</th>
                            <th class="text-left py-5 px-8 text-[10px] font-black text-muted-foreground uppercase tracking-[0.2em] border-b border-border/20">Qty Sold</th>
                            <th class="text-right py-5 px-8 text-[10px] font-black text-muted-foreground uppercase tracking-[0.2em] border-b border-border/20">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/20">
                        @foreach($topItems as $index => $item)
                            <tr class="group hover:bg-muted/30 transition-all cursor-pointer">
                                <td class="py-5 px-8">
                                    <div class="size-8 rounded-lg {{ $index === 0 ? 'bg-amber-500 text-white shadow-lg shadow-amber-500/20' : 'bg-muted/50 text-muted-foreground border border-border/50' }} flex items-center justify-center font-bold text-sm">
                                        {{ $index + 1 }}
                                    </div>
                                </td>
                                <td class="py-5 px-8 font-bold text-foreground">{{ $item->name }}</td>
                                <td class="py-5 px-8">
                                    <span class="px-3 py-1 bg-primary/5 text-primary text-[10px] font-black uppercase tracking-widest rounded-lg border border-primary/10">
                                        {{ $item->category }}
                                    </span>
                                </td>
                                <td class="py-5 px-8 font-medium text-muted-foreground">{{ number_format($item->qty) }} units</td>
                                <td class="py-5 px-8 text-right font-black text-foreground">{{ $settings['currency'] }}{{ number_format($item->revenue, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tax Summary -->
        <div class="bg-card border border-border/50 rounded-[3rem] p-8 shadow-sm flex flex-col">
            <div class="mb-10 text-center">
                <div class="size-16 bg-muted/40 rounded-[1.75rem] flex items-center justify-center mx-auto mb-6">
                    <i data-lucide="calculator" class="size-8 text-muted-foreground"></i>
                </div>
                <h3 class="text-2xl font-black text-foreground uppercase tracking-tight">Tax Summary</h3>
                <p class="text-xs text-muted-foreground font-medium mt-1">Financial Reconciliation Breakdown</p>
            </div>

            <div class="space-y-6 flex-1">
                <div class="p-6 bg-muted/20 border border-border/30 rounded-2xl group hover:bg-muted/40 transition-all">
                    <p class="text-[10px] font-black text-muted-foreground uppercase tracking-widest mb-1">Gross Sales</p>
                    <div class="flex items-baseline justify-between">
                        <h4 class="text-2xl font-black text-foreground">{{ $settings['currency'] }}{{ number_format($summary['revenue']['value'], 2) }}</h4>
                        <span class="text-[10px] font-bold text-emerald-700 dark:text-emerald-400">100%</span>
                    </div>
                </div>

                <div class="p-6 bg-muted/20 border border-border/30 rounded-2xl group hover:bg-muted/40 transition-all">
                    <p class="text-[10px] font-black text-orange-700 dark:text-orange-400 uppercase tracking-widest mb-1">Total Tax (5%)</p>
                    <div class="flex items-baseline justify-between">
                        <h4 class="text-2xl font-black text-foreground">{{ $settings['currency'] }}{{ number_format($summary['revenue']['value'] * 0.05, 2) }}</h4>
                        <span class="text-[10px] font-bold text-orange-700 dark:text-orange-400">Taxed</span>
                    </div>
                </div>

                <div class="p-6 bg-primary/5 border border-primary/20 rounded-2xl group hover:bg-primary/10 transition-all">
                    <p class="text-[10px] font-black text-primary uppercase tracking-widest mb-1">Net Sales</p>
                    <div class="flex items-baseline justify-between">
                        <h4 class="text-2xl font-black text-primary">{{ $settings['currency'] }}{{ number_format($summary['revenue']['value'] * 0.95, 2) }}</h4>
                        <span class="text-[10px] font-bold text-primary">Profit Base</span>
                    </div>
                </div>
            </div>

            <button class="w-full mt-10 h-16 rounded-2xl border-2 border-dashed border-border text-muted-foreground hover:border-primary hover:text-primary transition-all text-xs font-black uppercase tracking-widest flex items-center justify-center gap-2">
                <i data-lucide="file-text" class="size-4"></i>
                Generate Official Audit
            </button>
        </div>
    </div>

    @script
    <script>
        let salesChart, catChart;

        function initCharts() {
            if (salesChart) salesChart.destroy();
            if (catChart) catChart.destroy();

            const isDark = document.documentElement.classList.contains('dark');
            const primaryColor = getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim() || '#3b82f6';
            const labelColor = isDark ? '#94a3b8' : '#64748b';

            // Sales Trend
            const salesTrendData = @js($dailyTrend);
            const salesOptions = {
                series: [{
                    name: 'Revenue',
                    data: salesTrendData.values
                }],
                chart: {
                    type: 'area',
                    height: 320,
                    toolbar: { show: false },
                    animations: { enabled: true, easing: 'easeinout', speed: 800 },
                    background: 'transparent',
                    foreColor: labelColor
                },
                theme: {
                    mode: isDark ? 'dark' : 'light'
                },
                colors: [primaryColor],
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth', width: 3 },
                xaxis: {
                    categories: salesTrendData.labels,
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                    labels: { style: { colors: labelColor, fontSize: '12px', fontWeight: 600 } }
                },
                yaxis: {
                    labels: { 
                        formatter: (val) => '{{ $settings['currency'] }}' + val.toLocaleString(),
                        style: { colors: labelColor, fontSize: '12px', fontWeight: 600 }
                    }
                },
                grid: { borderColor: isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.05)', strokeDashArray: 4 },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.45,
                        opacityTo: 0.05,
                        stops: [20, 100]
                    }
                },
                tooltip: {
                    theme: isDark ? 'dark' : 'light',
                    y: { formatter: (val) => '{{ $settings['currency'] }}' + val.toLocaleString() }
                }
            };

            salesChart = new ApexCharts(document.querySelector("#salesTrendChart"), salesOptions);
            salesChart.render();

            // Category Distribution
            const catDistData = @js($categoryDistribution);
            const catOptions = {
                series: catDistData.values,
                chart: {
                    type: 'donut',
                    height: 320,
                },
                theme: {
                    mode: isDark ? 'dark' : 'light'
                },
                labels: catDistData.labels,
                colors: ['#f59e0b', '#6366f1', '#10b981', '#f43f5e', '#ec4899', '#06b6d4', '#3b82f6'],
                dataLabels: { enabled: false },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '75%',
                            labels: {
                                show: true,
                                name: { show: true, fontSize: '14px', fontWeight: 700, color: labelColor },
                                value: { 
                                    show: true, 
                                    fontSize: '24px', 
                                    fontWeight: 800,
                                    color: isDark ? '#f8fafc' : '#0f172a',
                                    formatter: (val) => '{{ $settings['currency'] }}' + parseInt(val).toLocaleString()
                                },
                                total: {
                                    show: true,
                                    label: 'Total',
                                    color: labelColor,
                                    formatter: function (w) {
                                        return '{{ $settings['currency'] }}' + w.globals.seriesTotals.reduce((a, b) => a + b, 0).toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                },
                legend: { position: 'bottom', fontSize: '12px', fontWeight: 600, labels: { colors: labelColor } },
                stroke: { show: false },
                tooltip: { theme: isDark ? 'dark' : 'light' },
                grid: { borderColor: isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.05)' }
            };

            catChart = new ApexCharts(document.querySelector("#categoryChart"), catOptions);
            catChart.render();
        }

        document.addEventListener('livewire:initialized', () => {
            initCharts();
            lucide.createIcons();
        });

        $wire.on('refreshCharts', () => {
            setTimeout(() => {
                initCharts();
                lucide.createIcons();
            }, 50);
        });

        window.addEventListener('theme-updated', () => {
            // Need a small timeout to let the DOM classes update
            setTimeout(() => {
                initCharts();
            }, 100);
        });
    </script>
    @endscript
</div>
