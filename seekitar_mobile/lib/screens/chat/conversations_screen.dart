import 'dart:async';

import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../core/text_utils.dart';
import '../../models/conversation.dart';
import '../../services/api_compat.dart';
import '../../services/dio_client.dart';
import '../../widgets/error_view.dart';

class ConversationsScreen extends StatefulWidget {
  const ConversationsScreen({super.key});
  @override State<ConversationsScreen> createState() => _ConversationsScreenState();
}

class _ConversationsScreenState extends State<ConversationsScreen> {
  final _api = ApiProvider();
  List<Conversation> _items = [];
  bool _loading = true;
  String? _error;

  @override void initState() { super.initState(); _load(); }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try { final c = await _api.getConversations(); if (mounted) setState(() { _items = c; _loading = false; }); }
    catch (e) { if (mounted) setState(() { _error = DioClient.friendly(e); _loading = false; }); }
  }

  @override Widget build(BuildContext ctx) => Scaffold(
    appBar: AppBar(title: const Text('Percakapan')),
    body: _loading ? const Center(child: CircularProgressIndicator())
      : _error != null ? ErrorView(message: _error!, onRetry: _load)
      : _items.isEmpty ? const Center(child: Text('Belum ada percakapan'))
      : RefreshIndicator(onRefresh: _load, child: ListView.builder(itemCount: _items.length, itemBuilder: (_, i) {
      final c = _items[i];
      return ListTile(
        leading: CircleAvatar(backgroundColor: c.hasUnread ? Colors.green.shade50 : Colors.grey.shade100, child: Text(avatarInitials(c.otherUserName, maxChars: 1), style: TextStyle(color: c.hasUnread ? Colors.green.shade700 : Colors.grey, fontWeight: FontWeight.bold))),
        title: Text(c.otherUserName ?? 'Pengguna', style: TextStyle(fontWeight: c.hasUnread ? FontWeight.bold : FontWeight.normal)),
        subtitle: Text(c.lastMessage ?? '', maxLines: 1, overflow: TextOverflow.ellipsis),
        trailing: c.hasUnread ? Container(width: 10, height: 10, decoration: const BoxDecoration(color: Colors.green, shape: BoxShape.circle)) : null,
        onTap: () => ctx.push('/chat/${c.id}', extra: c),
      );
    })),
  );
}

class ChatScreen extends StatefulWidget {
  final Conversation conversation;
  const ChatScreen({super.key, required this.conversation});
  @override State<ChatScreen> createState() => _ChatScreenState();
}

class _ChatScreenState extends State<ChatScreen> {
  final _api = ApiProvider();
  final _msgCtrl = TextEditingController();
  final _scrollCtrl = ScrollController();
  List<ChatMessage> _msgs = [];
  bool _sending = false;

  @override void initState() { super.initState(); _load(); }

  Future<void> _load() async {
    try { final ms = await _api.getMessages(widget.conversation.id); if (mounted) setState(() => _msgs = ms); }
    catch (_) {}
  }

  Future<void> _send() async {
    final txt = _msgCtrl.text.trim();
    if (txt.isEmpty || _sending) return;
    setState(() => _sending = true);
    try {
      await _api.sendMessage(widget.conversation.id, txt);
      // Kosongkan input HANYA setelah terkirim — kalau gagal, teks tetap
      // ada supaya pengguna bisa mencoba lagi.
      _msgCtrl.clear();
      if (mounted) await _load();
    } catch (e) {
      if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(DioClient.friendly(e))));
    }
    if (mounted) setState(() => _sending = false);
  }

  @override Widget build(BuildContext ctx) => Scaffold(
    appBar: AppBar(title: Text(widget.conversation.otherUserName ?? 'Chat')),
    body: Column(children: [
      Expanded(child: _msgs.isEmpty ? const Center(child: Text('Belum ada pesan')) : ListView.builder(controller: _scrollCtrl, reverse: true, padding: const EdgeInsets.all(12), itemCount: _msgs.length, itemBuilder: (_, i) => _bubble(_msgs[(_msgs.length - 1) - i]))),
      SafeArea(child: Padding(padding: const EdgeInsets.all(8), child: Row(children: [
        Expanded(child: TextField(controller: _msgCtrl, decoration: const InputDecoration(hintText: 'Ketik pesan...', border: OutlineInputBorder(), contentPadding: EdgeInsets.symmetric(horizontal: 14, vertical: 10)), textInputAction: TextInputAction.send, onSubmitted: (_) => unawaited(_send()))),
        const SizedBox(width: 8),
        CircleAvatar(backgroundColor: Theme.of(ctx).colorScheme.primary, child: IconButton(icon: const Icon(Icons.send, color: Colors.white, size: 20), onPressed: () => unawaited(_send()))),
      ]))),
    ]),
  );

  Widget _bubble(ChatMessage m) => Align(alignment: m.isMine ? Alignment.centerRight : Alignment.centerLeft, child: Container(margin: const EdgeInsets.only(bottom: 8), padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10), constraints: const BoxConstraints(maxWidth: 280), decoration: BoxDecoration(color: m.isMine ? Colors.green.shade100 : Colors.grey.shade100, borderRadius: BorderRadius.circular(12)), child: Text(m.message ?? '', style: const TextStyle(fontSize: 15))));

  @override void dispose() { _msgCtrl.dispose(); _scrollCtrl.dispose(); super.dispose(); }
}
