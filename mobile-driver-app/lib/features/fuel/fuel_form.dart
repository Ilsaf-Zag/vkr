import 'package:flutter/material.dart';

import '../../core/api_client.dart';

class FuelForm extends StatefulWidget {
  const FuelForm({
    required this.apiClient,
    required this.onSaved,
    super.key,
  });

  final ApiClient apiClient;
  final Future<void> Function() onSaved;

  @override
  State<FuelForm> createState() => _FuelFormState();
}

class _FuelFormState extends State<FuelForm> {
  final litersController = TextEditingController();
  final costController = TextEditingController();
  final odometerController = TextEditingController();
  final commentController = TextEditingController();
  String fuelType = 'diesel';
  bool loading = false;
  String? error;

  @override
  void dispose() {
    litersController.dispose();
    costController.dispose();
    odometerController.dispose();
    commentController.dispose();
    super.dispose();
  }

  Future<void> save() async {
    setState(() {
      loading = true;
      error = null;
    });

    try {
      await widget.apiClient.postJson('/mobile/fuel-logs', {
        'fuel_type': fuelType,
        'liters': double.parse(litersController.text.replaceAll(',', '.')),
        'cost': double.parse(costController.text.replaceAll(',', '.')),
        'odometer': int.parse(odometerController.text),
        'comment': commentController.text.trim().isEmpty ? null : commentController.text.trim(),
      });

      litersController.clear();
      costController.clear();
      odometerController.clear();
      commentController.clear();
      await widget.onSaved();
    } catch (exception) {
      setState(() => error = exception.toString());
    } finally {
      if (mounted) {
        setState(() => loading = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(18),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text('Добавить заправку', style: Theme.of(context).textTheme.titleMedium),
            const SizedBox(height: 12),
            DropdownButtonFormField<String>(
              value: fuelType,
              decoration: const InputDecoration(labelText: 'Тип топлива'),
              items: const [
                DropdownMenuItem(value: 'petrol', child: Text('Бензин')),
                DropdownMenuItem(value: 'gas', child: Text('Газ')),
                DropdownMenuItem(value: 'diesel', child: Text('Дизель')),
              ],
              onChanged: (value) => setState(() => fuelType = value ?? 'diesel'),
            ),
            const SizedBox(height: 10),
            TextField(
              controller: litersController,
              decoration: const InputDecoration(labelText: 'Количество литров'),
              keyboardType: TextInputType.number,
            ),
            const SizedBox(height: 10),
            TextField(
              controller: costController,
              decoration: const InputDecoration(labelText: 'Стоимость'),
              keyboardType: TextInputType.number,
            ),
            const SizedBox(height: 10),
            TextField(
              controller: odometerController,
              decoration: const InputDecoration(labelText: 'Одометр'),
              keyboardType: TextInputType.number,
            ),
            const SizedBox(height: 10),
            TextField(
              controller: commentController,
              decoration: const InputDecoration(labelText: 'Комментарий'),
              maxLines: 2,
            ),
            if (error != null) ...[
              const SizedBox(height: 10),
              Text(
                error!,
                style: TextStyle(color: Theme.of(context).colorScheme.error),
              ),
            ],
            const SizedBox(height: 12),
            FilledButton(
              onPressed: loading ? null : save,
              child: const Text('Сохранить заправку'),
            ),
          ],
        ),
      ),
    );
  }
}

