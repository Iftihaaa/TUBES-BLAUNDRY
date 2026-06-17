<x-filament-panels::page>
    <x-filament-widgets::widgets
        :columns="$this->getHeaderWidgetsColumns()"
        :data="$this->getWidgetData()"
        :widgets="$this->getHeaderWidgets()"
    />
</x-filament-panels::page>
