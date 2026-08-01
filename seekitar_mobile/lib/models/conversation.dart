import 'package:freezed_annotation/freezed_annotation.dart';
part 'conversation.freezed.dart';
part 'conversation.g.dart';

@freezed
class Conversation with _$Conversation {
  const factory Conversation({
    required String id,
    @JsonKey(name: 'order_id') String? orderId,
    @JsonKey(name: 'last_message') String? lastMessage,
    @JsonKey(name: 'last_message_at') DateTime? lastMessageAt,
    @JsonKey(name: 'other_user_name') String? otherUserName,
    @JsonKey(name: 'other_user_avatar') String? otherUserAvatar,
    @JsonKey(name: 'has_unread') @Default(false) bool hasUnread,
  }) = _Conversation;

  factory Conversation.fromJson(Map<String, dynamic> json) => _$ConversationFromJson(json);
}

@freezed
class ChatMessage with _$ChatMessage {
  const factory ChatMessage({
    required String id,
    @JsonKey(name: 'sender_id') required String senderId,
    String? message,
    @JsonKey(name: 'attachment_url') String? attachmentUrl,
    @JsonKey(name: 'message_type') @Default('text') String messageType,
    required DateTime createdAt,
    @Default(false) bool isMine,
  }) = _ChatMessage;

  factory ChatMessage.fromJson(Map<String, dynamic> json) => _$ChatMessageFromJson(json);
}
