import 'package:flutter/material.dart';

import '../../core/api_client.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({
    required this.apiClient,
    required this.onLoggedIn,
    super.key,
  });

  final ApiClient apiClient;
  final VoidCallback onLoggedIn;

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final loginController = TextEditingController();
  final passwordController = TextEditingController();
  bool loading = false;
  String? error;

  @override
  void dispose() {
    loginController.dispose();
    passwordController.dispose();
    super.dispose();
  }

  Future<void> submit() async {
    setState(() {
      loading = true;
      error = null;
    });

    try {
      await widget.apiClient.login(
        loginController.text.trim(),
        passwordController.text,
      );
      widget.onLoggedIn();
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
    return Scaffold(
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(20),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 420),
              child: Card(
                child: Padding(
                  padding: const EdgeInsets.all(20),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Text(
                        'АЗЫК',
                        style: Theme.of(context).textTheme.headlineSmall,
                      ),
                      const SizedBox(height: 4),
                      Text(
                        'Мобильное приложение водителя',
                        style: Theme.of(context).textTheme.bodyMedium,
                      ),
                      const SizedBox(height: 20),
                      TextField(
                        controller: loginController,
                        decoration: const InputDecoration(labelText: 'Логин'),
                        textInputAction: TextInputAction.next,
                      ),
                      const SizedBox(height: 12),
                      TextField(
                        controller: passwordController,
                        decoration: const InputDecoration(labelText: 'Пароль'),
                        obscureText: true,
                        onSubmitted: (_) => submit(),
                      ),
                      if (error != null) ...[
                        const SizedBox(height: 12),
                        Text(
                          error!,
                          style: TextStyle(color: Theme.of(context).colorScheme.error),
                        ),
                      ],
                      const SizedBox(height: 20),
                      FilledButton(
                        onPressed: loading ? null : submit,
                        child: loading
                            ? const SizedBox.square(
                                dimension: 18,
                                child: CircularProgressIndicator(strokeWidth: 2),
                              )
                            : const Text('Войти'),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}

