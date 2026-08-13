class Conversation {
  final String id;
  final String? orderId, lastMessage;
  final DateTime? lastMessageAt;
  final String? otherUserName, otherUserAvatar;
  final bool hasUnread;

  Conversation({required this.id, this.orderId, this.lastMessage, this.lastMessageAt, this.otherUserName, this.otherUserAvatar, this.hasUnread = false});

  factory Conversation.fromJson(Map<String, dynamic> json) => Conversation(
    id: json['id']?.toString() ?? '', orderId: json['order_id']?.toString(),
    lastMessage: json['last_message']?['message']?.toString(),
    lastMessageAt: json['last_message']?['created_at'] != null ? DateTime.tryParse(json['last_message']['created_at'].toString()) : null,
    otherUserName: ((json['participants'] as List?)?.firstWhere((p) => p['user_id'] != null, orElse: () => null) as Map?)?['user']?['name']?.toString(),
    otherUserAvatar: ((json['participants'] as List?)?.firstWhere((p) => p['user_id'] != null, orElse: () => null) as Map?)?['user']?['avatar_url']?.toString(),
    hasUnread: (json['unread_count'] as num?)?.toInt() > 0,
  );
}

class ChatMessage {
  final String id, senderId;
  final String? message, attachmentUrl;
  final String messageType;
  final DateTime createdAt;
  final bool isMine;

  ChatMessage({required this.id, required this.senderId, this.message, this.attachmentUrl, this.messageType = 'text', required this.createdAt, this.isMine = false});

  factory ChatMessage.fromJson(Map<String, dynamic> json) => ChatMessage(
    id: json['id']?.toString() ?? '', senderId: json['sender_id']?.toString() ?? '',
    message: json['message']?.toString(), attachmentUrl: json['attachment_url']?.toString(),
    messageType: json['message_type']?.toString() ?? 'text',
    createdAt: json['created_at'] != null ? DateTime.tryParse(json['created_at'].toString()) ?? DateTime.now() : DateTime.now(),
  );
}
