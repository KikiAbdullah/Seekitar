import 'package:flutter/material.dart';
import 'offline_banner.dart';

class BaseScreen extends StatelessWidget {
  final String? title; final List<Widget>? actions; final Widget body; final Widget? bottomNavigationBar; final Widget? floatingActionButton; final bool showAppBar;
  const BaseScreen({super.key, this.title, this.actions, required this.body, this.bottomNavigationBar, this.floatingActionButton, this.showAppBar = true});

  @override Widget build(BuildContext ctx) => Scaffold(
    appBar: showAppBar ? AppBar(title: title != null ? Text(title!, style: const TextStyle(fontWeight: FontWeight.w800)) : null, actions: actions) : null,
    body: OfflineBanner(child: body),
    bottomNavigationBar: bottomNavigationBar,
    floatingActionButton: floatingActionButton,
  );
}
