import 'package:flutter/material.dart';
import '../models/conversation.dart';
import '../services/api_compat.dart';

class ConversationsScreen extends StatefulWidget {
  const ConversationsScreen({super.key});
  @override State<ConversationsScreen> createState() => _ConversationsScreenState();
}

class _ConversationsScreenState extends State<ConversationsScreen> {
  final _api = ApiProvider();
  List<Conversation> _items = [];
  bool _loading = true;

  @override void initState() { super.initState(); _load(); }

  Future<void> _load() async {
    setState(() => _loading = true);
    try { final c = await _api.getConversations(); if (mounted) setState(() { _items = c; _loading = false; }); }
    catch (_) { if (mounted) setState(() => _loading = false); }
  }

  @override Widget build(BuildContext ctx) => Scaffold(
    appBar: AppBar(title: const Text('Percakapan')),
    body: _loading ? const Center(child: CircularProgressIndicator()) : _items.isEmpty ? const Center(child: Text('Belum ada percakapan')) : RefreshIndicator(onRefresh: _load, child: ListView.builder(itemCount: _items.length, itemBuilder: (_, i) {
      final c = _items[i];
      return ListTile(
        leading: CircleAvatar(backgroundColor: c.hasUnread ? Colors.green.shade50 : Colors.grey.shade100, child: Text((c.otherUserName ?? 'U').substring(0, 1).toUpperCase(), style: TextStyle(color: c.hasUnread ? Colors.green.shade700 : Colors.grey, fontWeight: FontWeight.bold))),
        title: Text(c.otherUserName ?? 'Pengguna', style: TextStyle(fontWeight: c.hasUnread ? FontWeight.bold : FontWeight.normal)),
        subtitle: Text(c.lastMessage ?? '', maxLines: 1, overflow: TextOverflow.ellipsis),
        trailing: c.hasUnread ? Container(width: 10, height: 10, decoration: const BoxDecoration(color: Colors.green, shape: BoxShape.circle)) : null,
        onTap: () => Navigator.push(ctx, MaterialPageRoute(builder: (_) => ChatScreen(conversation: c))),
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
  String? _myId;

  @override void initState() { super.initState(); _load(); }

  Future<void> _load() async {
    try { final ms = await _api.getMessages(widget.conversation.id); if (mounted) setState(() => _msgs = ms); }
    catch (_) {}
  }

  Future<void> _send() async {
    final txt = _msgCtrl.text.trim();
    if (txt.isEmpty) return;
    _msgCtrl.clear();
    try { await _api.sendMessage(widget.conversation.id, txt); _load(); }
    catch (_) {}
  }

  @override Widget build(BuildContext ctx) => Scaffold(
    appBar: AppBar(title: Text(widget.conversation.otherUserName ?? 'Chat')),
    body: Column(children: [
      Expanded(child: _msgs.isEmpty ? const Center(child: Text('Belum ada pesan')) : ListView.builder(controller: _scrollCtrl, reverse: true, padding: const EdgeInsets.all(12), itemCount: _msgs.length, itemBuilder: (_, i) => _bubble(_msgs[(_msgs.length - 1) - i]))),
      SafeArea(child: Padding(padding: const EdgeInsets.all(8), child: Row(children: [
        Expanded(child: TextField(controller: _msgCtrl, decoration: const InputDecoration(hintText: 'Ketik pesan...', border: OutlineInputBorder(), contentPadding: EdgeInsets.symmetric(horizontal: 14, vertical: 10)), textInputAction: TextInputAction.send, onSubmitted: (_) => _send())),
        const SizedBox(width: 8),
        CircleAvatar(backgroundColor: Theme.of(ctx).colorScheme.primary, child: IconButton(icon: const Icon(Icons.send, color: Colors.white, size: 20), onPressed: _send)),
      ]))),
    ]),
  );

  Widget _bubble(ChatMessage m) => Align(alignment: m.isMine ? Alignment.centerRight : Alignment.centerLeft, child: Container(margin: const EdgeInsets.only(bottom: 8), padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10), constraints: const BoxConstraints(maxWidth: 280), decoration: BoxDecoration(color: m.isMine ? Colors.green.shade100 : Colors.grey.shade100, borderRadius: BorderRadius.circular(12)), child: Text(m.message ?? '', style: const TextStyle(fontSize: 15))));

  @override void dispose() { _msgCtrl.dispose(); _scrollCtrl.dispose(); super.dispose(); }
}
