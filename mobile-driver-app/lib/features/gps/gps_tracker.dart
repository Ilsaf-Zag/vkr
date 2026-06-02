import 'dart:async';

import 'package:geolocator/geolocator.dart';

import '../../core/api_client.dart';

class GpsTracker {
  GpsTracker({required this.apiClient});

  final ApiClient apiClient;
  Timer? _timer;
  bool enabled = false;

  Future<void> start() async {
    final permission = await _ensurePermission();
    enabled = permission;

    if (!permission) {
      return;
    }

    _timer = Timer.periodic(const Duration(seconds: 10), (_) => sendPoint());
    await sendPoint();
  }

  void stop() {
    _timer?.cancel();
    _timer = null;
  }

  Future<void> sendPoint() async {
    try {
      final position = await _readPosition();
      if (position == null) {
        return;
      }

      await apiClient.postJson('/mobile/gps-points', {
        'latitude': position.latitude,
        'longitude': position.longitude,
        'speed': position.speed < 0 ? null : position.speed,
        'heading': position.heading < 0 ? null : position.heading,
        'recorded_at': DateTime.now().toIso8601String(),
      });
    } catch (_) {
      // GPS errors should not block the driver's workflow screen.
    }
  }

  Future<Position?> _readPosition() async {
    try {
      return await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.medium,
        timeLimit: const Duration(seconds: 8),
      );
    } catch (_) {
      try {
        return await Geolocator.getLastKnownPosition();
      } catch (_) {
        return null;
      }
    }
  }

  Future<bool> _ensurePermission() async {
    bool serviceEnabled;
    try {
      serviceEnabled = await Geolocator.isLocationServiceEnabled();
    } catch (_) {
      return false;
    }

    if (!serviceEnabled) {
      return false;
    }

    try {
      var permission = await Geolocator.checkPermission();

      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
      }

      return permission == LocationPermission.always || permission == LocationPermission.whileInUse;
    } catch (_) {
      return false;
    }
  }
}
