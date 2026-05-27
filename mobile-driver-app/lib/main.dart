import 'package:flutter/material.dart';

import 'core/api_client.dart';
import 'core/session_store.dart';
import 'features/auth/login_screen.dart';
import 'features/workflow/driver_workflow_screen.dart';

const apiBaseUrl = String.fromEnvironment(
  'API_BASE_URL',
  defaultValue: 'http://localhost:8000/api',
);

void main() {
  runApp(const AzykDriverApp());
}

class AzykDriverApp extends StatefulWidget {
  const AzykDriverApp({super.key});

  @override
  State<AzykDriverApp> createState() => _AzykDriverAppState();
}

class _AzykDriverAppState extends State<AzykDriverApp> {
  final sessionStore = SessionStore();
  late final apiClient = ApiClient(
    sessionStore: sessionStore,
    baseUrl: apiBaseUrl,
  );

  bool loading = true;
  bool authenticated = false;

  @override
  void initState() {
    super.initState();
    restore();
  }

  Future<void> restore() async {
    authenticated = await sessionStore.hasToken();
    loading = false;
    if (mounted) {
      setState(() {});
    }
  }

  Future<void> onLoggedIn() async {
    authenticated = true;
    setState(() {});
  }

  Future<void> onLoggedOut() async {
    await sessionStore.clear();
    authenticated = false;
    setState(() {});
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'АЗЫК Водитель',
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xFF1F6F78)),
        useMaterial3: true,
      ),
      home: loading
          ? const Scaffold(body: Center(child: CircularProgressIndicator()))
          : authenticated
              ? DriverWorkflowScreen(apiClient: apiClient, onLoggedOut: onLoggedOut)
              : LoginScreen(apiClient: apiClient, onLoggedIn: onLoggedIn),
    );
  }
}
