import 'dart:async';

import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

import '../../core/api_client.dart';
import '../fuel/fuel_form.dart';
import '../gps/gps_tracker.dart';
import 'workflow_step.dart';

String fuelTypeLabel(dynamic value) {
  return switch (value?.toString()) {
    'petrol' => 'Бензин',
    'gas' => 'Газ',
    'diesel' => 'Дизель',
    _ => value?.toString() ?? '—',
  };
}

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
  Timer? pollingTimer;

  WorkflowStep get step => WorkflowStep.fromApi(workflow?['step']?.toString());

  @override
  void initState() {
    super.initState();
    loadWorkflow();
  }

  @override
  void dispose() {
    pollingTimer?.cancel();
    super.dispose();
  }

  Future<void> loadWorkflow({bool silent = false}) async {
    if (!silent) {
      setState(() {
        loading = true;
        error = null;
      });
    }

    try {
      workflow = await widget.apiClient.getJson('/mobile/workflow');
      error = null;
    } catch (exception) {
      error = exception.toString();
    } finally {
      if (mounted) {
        setState(() => loading = false);
        configurePolling();
      }
    }
  }

  void configurePolling() {
    pollingTimer?.cancel();

    if (!isWaitingForInspection(step)) {
      return;
    }

    pollingTimer = Timer.periodic(const Duration(seconds: 5), (_) {
      if (mounted && !loading) {
        loadWorkflow(silent: true);
      }
    });
  }

  bool isWaitingForInspection(WorkflowStep value) {
    return {
      WorkflowStep.preTripMedicalWaiting,
      WorkflowStep.preTripTechnicalWaiting,
      WorkflowStep.postTripMedicalWaiting,
      WorkflowStep.postTripTechnicalWaiting,
    }.contains(value);
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
          onOpenWaybill: () => post('/mobile/waybills/open'),
        ),
      WorkflowStep.startOdometer => OdometerCapturePanel(
          apiClient: widget.apiClient,
          workflow: workflow,
          captureType: 'start',
          title: 'Зафиксируйте начальные показания одометра',
          onConfirmed: loadWorkflow,
        ),
      WorkflowStep.preTripMedical => StepPanel(
          title: 'Предрейсовый медосмотр',
          text: 'Запрос отправляется медицинскому работнику.',
          actionLabel: 'Запросить медосмотр',
          onAction: () => post('/mobile/inspections/medical/request', {'type': 'pre_trip'}),
        ),
      WorkflowStep.preTripMedicalWaiting => const WaitingPanel(
          title: 'Медосмотр запрошен',
          text: 'Ожидайте решение медицинского работника. Экран обновится автоматически.',
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
      WorkflowStep.preTripTechnicalWaiting => const WaitingPanel(
          title: 'Техосмотр запрошен',
          text: 'Ожидайте решение механика. Экран обновится автоматически.',
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
          onFinish: () => post('/mobile/shift/finish-trip'),
          onChanged: loadWorkflow,
        ),
      WorkflowStep.finishOdometer => OdometerCapturePanel(
          apiClient: widget.apiClient,
          workflow: workflow,
          captureType: 'finish',
          title: 'Зафиксируйте конечные показания одометра',
          onConfirmed: loadWorkflow,
        ),
      WorkflowStep.postTripMedical => StepPanel(
          title: 'Послерейсовый медосмотр',
          text: 'Запрос отправляется медицинскому работнику.',
          actionLabel: 'Запросить медосмотр',
          onAction: () => post('/mobile/inspections/medical/request', {'type': 'post_trip'}),
        ),
      WorkflowStep.postTripMedicalWaiting => const WaitingPanel(
          title: 'Послерейсовый медосмотр запрошен',
          text: 'Ожидайте решение медицинского работника. Экран обновится автоматически.',
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
      WorkflowStep.postTripTechnicalWaiting => const WaitingPanel(
          title: 'Послерейсовый техосмотр запрошен',
          text: 'Ожидайте решение механика. Экран обновится автоматически.',
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

class WaitingPanel extends StatelessWidget {
  const WaitingPanel({
    required this.title,
    required this.text,
    super.key,
  });

  final String title;
  final String text;

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
            const LinearProgressIndicator(),
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
  final Future<void> Function() onOpenWaybill;

  @override
  State<WorkOrderPanel> createState() => _WorkOrderPanelState();
}

class _WorkOrderPanelState extends State<WorkOrderPanel> {
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
            const Text('После открытия путевого листа нужно сфотографировать одометр и подтвердить значение.'),
            const SizedBox(height: 18),
            FilledButton(
              onPressed: widget.onOpenWaybill,
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
  final Future<void> Function() onFinish;
  final Future<void> Function() onChanged;

  @override
  State<ActiveShiftPanel> createState() => _ActiveShiftPanelState();
}

class _ActiveShiftPanelState extends State<ActiveShiftPanel> {
  late final GpsTracker gpsTracker = GpsTracker(apiClient: widget.apiClient);

  @override
  void initState() {
    super.initState();
    gpsTracker.start();
  }

  @override
  void dispose() {
    gpsTracker.stop();
    super.dispose();
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
                    title: Text('${fuelTypeLabel(item['fuel_type'])} — ${item['liters']} л'),
                    subtitle: Text('Стоимость: ${item['cost']}'),
                  ),
              ],
            ),
          ),
        ),
        const SizedBox(height: 12),
        FilledButton.tonal(
          onPressed: widget.onFinish,
          child: const Text('Завершить смену'),
        ),
      ],
    );
  }
}

class OdometerCapturePanel extends StatefulWidget {
  const OdometerCapturePanel({
    required this.apiClient,
    required this.workflow,
    required this.captureType,
    required this.title,
    required this.onConfirmed,
    super.key,
  });

  final ApiClient apiClient;
  final Map<String, dynamic>? workflow;
  final String captureType;
  final String title;
  final Future<void> Function() onConfirmed;

  @override
  State<OdometerCapturePanel> createState() => _OdometerCapturePanelState();
}

class _OdometerCapturePanelState extends State<OdometerCapturePanel> {
  final picker = ImagePicker();
  final valueController = TextEditingController();
  Map<String, dynamic>? capture;
  Map<String, dynamic>? odometerControl;
  bool processing = false;
  String? error;

  int get waybillId {
    final waybill = widget.workflow?['waybill'] as Map<String, dynamic>?;
    return (waybill?['id'] as num).toInt();
  }

  @override
  void initState() {
    super.initState();
    odometerControl = widget.workflow?['odometer_control'] as Map<String, dynamic>?;
    capture = odometerControl?[widget.captureType] as Map<String, dynamic>?;
    final value = capture?['confirmed_value'] ?? capture?['ocr_value'];
    if (value != null) {
      valueController.text = value.toString();
    }
  }

  @override
  void dispose() {
    valueController.dispose();
    super.dispose();
  }

  Future<void> pickAndUpload(ImageSource source) async {
    setState(() {
      processing = true;
      error = null;
    });

    try {
      final image = await picker.pickImage(
        source: source,
        imageQuality: 80,
        maxWidth: 1920,
        maxHeight: 1920,
      );
      if (image == null) return;

      final data = await widget.apiClient.postMultipart(
        '/mobile/waybills/$waybillId/odometer-captures',
        fields: {'capture_type': widget.captureType},
        fileField: 'image',
        fileBytes: await image.readAsBytes(),
        filename: image.name,
      );

      capture = data['capture'] as Map<String, dynamic>?;
      odometerControl = data['odometer_control'] as Map<String, dynamic>?;
      final recognizedValue = capture?['ocr_value'];
      valueController.text = recognizedValue?.toString() ?? '';
    } catch (exception) {
      error = exception.toString();
    } finally {
      if (mounted) {
        setState(() => processing = false);
      }
    }
  }

  Future<void> confirm() async {
    final value = int.tryParse(valueController.text.trim());
    if (capture == null || value == null) {
      setState(() => error = 'Укажите значение одометра перед подтверждением.');
      return;
    }

    final startValue = (odometerControl?['start_odometer_confirmed'] as num?)?.toInt();
    if (widget.captureType == 'finish' && startValue != null && value < startValue) {
      setState(() => error = 'Конечное значение одометра не может быть меньше начального.');
      return;
    }

    setState(() {
      processing = true;
      error = null;
    });

    try {
      final data = await widget.apiClient.postJson(
        '/mobile/waybills/$waybillId/odometer-captures/${capture!['id']}/confirm',
        {'confirmed_value': value},
      );
      odometerControl = data['odometer_control'] as Map<String, dynamic>?;

      if (widget.captureType == 'finish') {
        final distance = odometerControl?['odometer_distance_km'];
        if (mounted && distance != null) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Пробег по одометру: $distance км')),
          );
        }
      }

      await widget.onConfirmed();
    } catch (exception) {
      error = exception.toString();
    } finally {
      if (mounted) {
        setState(() => processing = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final status = capture?['recognition_status']?.toString();
    final ocrValue = capture?['ocr_value'];
    final candidates = (capture?['ocr_candidates'] as List<dynamic>?) ?? [];

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(18),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(widget.title, style: Theme.of(context).textTheme.titleLarge),
            const SizedBox(height: 8),
            const Text('Сфотографируйте приборную панель. Проверьте значение перед подтверждением.'),
            const SizedBox(height: 16),
            if (processing) ...[
              const LinearProgressIndicator(),
              const SizedBox(height: 12),
              const Text('Распознаём показания одометра…'),
            ],
            if (error != null) ErrorPanel(message: error!),
            if (status == 'failed')
              ErrorPanel(message: capture?['recognition_error']?.toString() ?? 'Не удалось распознать показания.'),
            if (ocrValue != null) ...[
              Text('Распознано: $ocrValue км', style: Theme.of(context).textTheme.titleMedium),
            ],
            if (candidates.length > 1) ...[
              const SizedBox(height: 10),
              const Text('Найденные варианты:'),
              Wrap(
                spacing: 8,
                children: [
                  for (final item in candidates)
                    ChoiceChip(
                      label: Text('${item['value']}'),
                      selected: valueController.text == item['value'].toString(),
                      onSelected: (_) => setState(() => valueController.text = item['value'].toString()),
                    ),
                ],
              ),
            ],
            const SizedBox(height: 12),
            TextField(
              controller: valueController,
              decoration: const InputDecoration(
                labelText: 'Подтверждаемое значение, км',
                helperText: 'Можно исправить значение, если распознавание ошиблось.',
              ),
              keyboardType: TextInputType.number,
            ),
            const SizedBox(height: 16),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                FilledButton.icon(
                  onPressed: processing ? null : () => pickAndUpload(ImageSource.camera),
                  icon: const Icon(Icons.photo_camera),
                  label: const Text('Сфотографировать одометр'),
                ),
                OutlinedButton.icon(
                  onPressed: processing ? null : () => pickAndUpload(ImageSource.gallery),
                  icon: const Icon(Icons.photo_library),
                  label: const Text('Выбрать фото'),
                ),
                FilledButton.tonal(
                  onPressed: processing ? null : confirm,
                  child: const Text('Подтвердить'),
                ),
              ],
            ),
          ],
        ),
      ),
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
