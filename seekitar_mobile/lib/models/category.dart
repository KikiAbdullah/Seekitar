class Category {
  final int id;
  final String name;
  final String? icon;
  final int sortOrder;
  final List<Category> children;
  Category({required this.id, required this.name, this.icon, this.sortOrder = 0, this.children = const []});

  factory Category.fromJson(Map<String, dynamic> json) => Category(
    id: json['id'] ?? 0, name: json['name']?.toString() ?? '',
    icon: json['icon']?.toString(), sortOrder: json['sort_order'] ?? 0,
    children: (json['children'] as List<dynamic>?)?.map((c) => Category.fromJson(c as Map<String,dynamic>)).toList() ?? [],
  );
}
