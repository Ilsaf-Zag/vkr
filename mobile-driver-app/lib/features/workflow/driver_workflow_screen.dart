import 'dart:async';

import 'package:flutter/material.dart';

import '../../core/api_client.dart';
import '../fuel/fuel_form.dart';
import '../gps/gps_tracker.dart';
import 'workflow_step.dart';

class DriverWorkflowScreen extends StatefulWidget {
  const DriverWorkflowScreen({
    required this.apiClient,
    required this.onLoggedOut,
    super.key,
  });

  final ApiClient apiClient;
  final Future<void> Function() onLoggedOut;

  @override
  State<DriverWorkflowScreen> createState() => _DriverWorkflowScreenState();
}

class _DriverWorkflowScreenState extends State<DriverWorkflowScreen> {
  Map<String, dynamic>? workflow;
  bool loading = true;
  String? error;

  WorkflowStep get step => WorkflowStep.fromApi(workflow?['step']?.toString());

  @override
  void initState() {
    super.initState();
    loadWorkflow();
  }

  Future<void> loadWorkflow() async {
    setState(() {
      loading = true;
      error = null;
    });

    try {
      workflow = await widget.apiClient.getJson('/mobile/workflow');
    } catch (exception) {
      error = exception.toString();
    } finally {
      if (mounted) {
        setState(() => loading = false);
      }
    }
  }

  Future<void> post(String path, [Map<String, dynamic>? body]) async {
    setState(() {
      loading = true;
      error = null;
    });

    try {
      await widget.apiClient.postJson(path, body);
      await loadWorkflow();
    } catch (exception) {
      setState(() => error = exception.toString());
    } finally {
      if (mounted) {
        setState(() => loading = false);
      }
    }
  }

  Future<void> printImitation(String path) async {
    setState(() => loading = true);
    await Future<void>.delayed(const Duration(seconds: 5));
    await post(path);
  }

  Future<void> logout() async {
    await widget.apiClient.logout();
    await widget.onLoggedOut();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Смена водителя'),
        actions: [
          IconButton(
            onPressed: loading ? null : loadWorkflow,
            icon: const Icon(Icons.refresh),
            tooltip: 'Обновить',
          ),
        ],
      ),
      body: SafeArea(
        child: loading
            ? const Center(child: CircularProgressIndicator())
            : RefreshIndicator(
                onRefresh: loadWorkflow,
                child: ListView(
                  padding: const EdgeInsets.all(16),
                  children: [
                    if (error != null) ErrorPanel(message: error!),
                    buildStep(context),
                  ],
                ),
              ),
      ),
    );
  }

  Widget buildStep(BuildContext context) {
    return switch (step) {
      WorkflowStep.noWorkOrder => StepPanel(
          title: 'На текущую смену отсутствует план-наряд',
          text: 'Обратитесь к диспетчеру.',
          actionLabel: 'Выйти',
          onAction: logout,
        ),
      WorkflowStep.workOrderFound => WorkOrderPanel(
          workOrder: workflow?['work_order'] as Map<String, dynamic>?,
          onOpenWaybill: (odometerStart) => post('/mobile/waybills/open', {
            if (odometerStart != null) 'odometer_start': odometerStart,
          }),
        ),
      WorkflowStep.preTripMedical => StepPanel(
          title: 'Предрейсовый медосмотр',
          text: 'Запрос отправляется медицинскому работнику.',
          actionLabel: 'Запросить медосмотр',
          onAction: () => post('/mobile/inspections/medical/request', {'type': 'pre_trip'}),
        ),
      WorkflowStep.preTripMedicalRejected => BlockedPanel(
          title: 'Предрейсовый медосмотр отклонен',
          onLogout: logout,
        ),
      WorkflowStep.preTripTechnical => StepPanel(
          title: 'Предрейсовый техосмотр',
          text: 'Запрос отправляется механику.',
          actionLabel: 'Запросить техосмотр',
          onAction: () => post('/mobile/inspections/technical/request', {'type': 'pre_trip'}),
        ),
      WorkflowStep.preTripTechnicalRejected => BlockedPanel(
          title: 'Предрейсовый техосмотр отклонен',
          onLogout: logout,
        ),
      WorkflowStep.initialPrint => StepPanel(
          title: 'Печать путевого листа',
          text: 'Имитация печати займет 5 секунд.',
          actionLabel: 'Распечатать ПЛ',
          onAction: () => printImitation('/mobile/waybills/initial-print-done'),
        ),
      WorkflowStep.startShift => StepPanel(
          title: 'Начало смены',
          text: 'После начала смены включится отправка GPS.',
          actionLabel: 'Начать смену',
          onAction: () => post('/mobile/shift/start'),
        ),
      WorkflowStep.activeShift => ActiveShiftPanel(
          apiClient: widget.apiClient,
          workflow: workflow,
          onFinish: (odometerEnd) => post('/mobile/shift/finish-trip', {
            if (odometerEnd != null) 'odometer_end': odometerEnd,
          }),
          onChanged: loadWorkflow,
        ),
      WorkflowStep.postTripMedical => StepPanel(
          title: 'Послерейсовый медосмотр',
          text: 'Запрос отправляется медицинскому работнику.',
          actionLabel: 'Запросить медосмотр',
          onAction: () => post('/mobile/inspections/medical/request', {'type': 'post_trip'}),
        ),
      WorkflowStep.postTripMedicalRejected => BlockedPanel(
          title: 'Послерейсовый медосмотр отклонен',
          onLogout: logout,
        ),
      WorkflowStep.postTripTechnical => StepPanel(
          title: 'Послерейсовый техосмотр',
          text: 'Запрос отправляется механику.',
          actionLabel: 'Запросить техосмотр',
          onAction: () => post('/mobile/inspections/technical/request', {'type': 'post_trip'}),
        ),
      WorkflowStep.postTripTechnicalRejected => BlockedPanel(
          title: 'Послерейсовый техосмотр отклонен',
          onLogout: logout,
        ),
      WorkflowStep.finalPrint => StepPanel(
          title: 'Печать итоговых данных',
          text: 'Имитация печати займет 5 секунд.',
          actionLabel: 'Распечатать данные',
          onAction: () => printImitation('/mobile/waybills/final-print-done'),
        ),
      WorkflowStep.closeShift => StepPanel(
          title: 'Закрытие смены',
          text: 'После закрытия приложение вернется на экран входа.',
          actionLabel: 'Окончательно завершить смену',
          onAction: () async {
            await widget.apiClient.postJson('/mobile/shift/close');
            await widget.onLoggedOut();
          },
        ),
      WorkflowStep.closed => StepPanel(
          title: 'Смена закрыта',
          text: 'Работа с путевым листом завершена.',
          actionLabel: 'Выйти',
          onAction: logout,
        ),
      WorkflowStep.cancelled => StepPanel(
          title: 'Путевой лист отменен',
          text: 'Обратитесь к диспетчеру.',
          actionLabel: 'Выйти',
          onAction: logout,
        ),
    };
  }
}

class StepPanel extends StatelessWidget {
  const StepPanel({
    required this.title,
    required this.text,
    required this.actionLabel,
    required this.onAction,
    super.key,
  });

  final String title;
  final String text;
  final String actionLabel;
  final FutureOr<void> Function() onAction;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(18),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(title, style: Theme.of(context).textTheme.titleLarge),
            const SizedBox(height: 8),
            Text(text),
            const SizedBox(height: 18),
            FilledButton(
              onPressed: () => onAction(),
              child: Text(actionLabel),
            ),
          ],
        ),
      ),
    );
  }
}

class WorkOrderPanel extends StatefulWidget {
  const WorkOrderPanel({
    required this.workOrder,
    required this.onOpenWaybill,
    super.key,
  });

  final Map<String, dynamic>? workOrder;
  final Future<void> Function(int? odometerStart) onOpenWaybill;

  @override
  State<WorkOrderPanel> createState() => _WorkOrderPanelState();
}

class _WorkOrderPanelState extends State<WorkOrderPanel> {
  final odometerController = TextEditingController();

  @override
  void dispose() {
    odometerController.dispose();
    super.dispose();
  }

  int? get odometerStart {
    final value = odometerController.text.trim();
    return value.isEmpty ? null : int.tryParse(value);
  }

  @override
  Widget build(BuildContext context) {
    final vehicle = widget.workOrder?['vehicle'] as Map<String, dynamic>?;
    final driver = widget.workOrder?['driver'] as Map<String, dynamic>?;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(18),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text('План-наряд найден', style: Theme.of(context).textTheme.titleLarge),
            const SizedBox(height: 12),
            InfoRow(label: 'Водитель', value: driver?['full_name']?.toString() ?? ''),
            InfoRow(label: 'Автомобиль', value: '${vehicle?['brand'] ?? ''} ${vehicle?['model'] ?? ''}'),
            InfoRow(label: 'Дата', value: widget.workOrder?['date']?.toString() ?? ''),
            InfoRow(label: 'Смена', value: widget.workOrder?['shift']?.toString() ?? ''),
            InfoRow(label: 'Маршрут', value: widget.workOrder?['route_name']?.toString() ?? ''),
            const SizedBox(height: 12),
            TextField(
              controller: odometerController,
              decoration: const InputDecoration(labelText: 'Одометр на начало'),
              keyboardType: TextInputType.number,
            ),
            const SizedBox(height: 18),
            FilledButton(
              onPressed: () => widget.onOpenWaybill(odometerStart),
              child: const Text('Открыть путевой лист'),
            ),
          ],
        ),
      ),
    );
  }
}

class ActiveShiftPanel extends StatefulWidget {
  const ActiveShiftPanel({
    required this.apiClient,
    required this.workflow,
    required this.onFinish,
    required this.onChanged,
    super.key,
  });

  final ApiClient apiClient;
  final Map<String, dynamic>? workflow;
  final Future<void> Function(int? odometerEnd) onFinish;
  final Future<void> Function() onChanged;

  @override
  State<ActiveShiftPanel> createState() => _ActiveShiftPanelState();
}

class _ActiveShiftPanelState extends State<ActiveShiftPanel> {
  late final GpsTracker gpsTracker = GpsTracker(apiClient: widget.apiClient);
  final odometerEndController = TextEditingController();

  @override
  void initState() {
    super.initState();
    gpsTracker.start();
  }

  @override
  void dispose() {
    gpsTracker.stop();
    odometerEndController.dispose();
    super.dispose();
  }

  int? get odometerEnd {
    final value = odometerEndController.text.trim();
    return value.isEmpty ? null : int.tryParse(value);
  }

  @override
  Widget build(BuildContext context) {
    final waybill = widget.workflow?['waybill'] as Map<String, dynamic>?;
    final vehicle = waybill?['vehicle'] as Map<String, dynamic>?;
    final fuelLogs = (waybill?['fuel_logs'] as List<dynamic>?) ?? [];

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Card(
          child: Padding(
            padding: const EdgeInsets.all(18),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Смена в процессе', style: Theme.of(context).textTheme.titleLarge),
                const SizedBox(height: 12),
                InfoRow(label: 'Путевой лист', value: waybill?['number']?.toString() ?? ''),
                InfoRow(label: 'Автомобиль', value: '${vehicle?['brand'] ?? ''} ${vehicle?['model'] ?? ''}'),
                InfoRow(label: 'Маршрут', value: waybill?['route_name']?.toString() ?? ''),
                InfoRow(label: 'GPS', value: gpsTracker.enabled ? 'активен' : 'ожидает разрешение'),
              ],
            ),
          ),
        ),
        const SizedBox(height: 12),
        FuelForm(apiClient: widget.apiClient, onSaved: widget.onChanged),
        const SizedBox(height: 12),
        Card(
          child: Padding(
            padding: const EdgeInsets.all(18),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Заправки', style: Theme.of(context).textTheme.titleMedium),
                const SizedBox(height: 8),
                if (fuelLogs.isEmpty) const Text('Нет записей'),
                for (final item in fuelLogs)
                  ListTile(
                    contentPadding: EdgeInsets.zero,
                    title: Text('${item['fuel_type']} — ${item['liters']} л'),
                    subtitle: Text('Одометр: ${item['odometer']}, стоимость: ${item['cost']}'),
                  ),
              ],
            ),
          ),
        ),
        const SizedBox(height: 12),
        TextField(
          controller: odometerEndController,
          decoration: const InputDecoration(labelText: 'Одометр на конец'),
          keyboardType: TextInputType.number,
        ),
        const SizedBox(height: 12),
        FilledButton.tonal(
          onPressed: () => widget.onFinish(odometerEnd),
          child: const Text('Завершить смену'),
        ),
      ],
    );
  }
}

class BlockedPanel extends StatelessWidget {
  const BlockedPanel({
    required this.title,
    required this.onLogout,
    super.key,
  });

  final String title;
  final Future<void> Function() onLogout;

  @override
  Widget build(BuildContext context) {
    return StepPanel(
      title: title,
      text: 'Дальнейшее прохождение смены заблокировано.',
      actionLabel: 'Вернуться на экран входа',
      onAction: onLogout,
    );
  }
}

class ErrorPanel extends StatelessWidget {
  const ErrorPanel({required this.message, super.key});

  final String message;

  @override
  Widget build(BuildContext context) {
    return Card(
      color: Theme.of(context).colorScheme.errorContainer,
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Text(message),
      ),
    );
  }
}

class InfoRow extends StatelessWidget {
  const InfoRow({
    required this.label,
    required this.value,
    super.key,
  });

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 120,
            child: Text(label, style: const TextStyle(fontWeight: FontWeight.w600)),
          ),
          Expanded(child: Text(value.isEmpty ? '—' : value)),
        ],
      ),
    );
  }
}
