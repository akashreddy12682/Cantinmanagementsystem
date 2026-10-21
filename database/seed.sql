-- ============================================================
-- CANTEEN MANAGEMENT SYSTEM
-- Seed Data (Categories, Food Items, Users, Initial Orders)
-- ============================================================

USE `canteen_db`;

-- ------------------------------------------------------------
-- 1. Insert Categories
-- ------------------------------------------------------------
INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'Breakfast', 'Delicious and hot morning favorites made fresh every day'),
(2, 'Meals', 'Hearty lunch and dinner plates, thalis, and combo meals'),
(3, 'Snacks', 'Crispy, savory snacks and quick bites for study breaks'),
(4, 'Beverages', 'Refreshing hot teas, artisan filter coffee, cold drinks, and shakes'),
(5, 'Desserts', 'Sweet treats, traditional Indian desserts, and pastries')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `description`=VALUES(`description`);

-- ------------------------------------------------------------
-- 2. Insert Users (Admin & Customer)
-- Default passwords:
-- Admin:    Admin@123
-- Customer: Customer@123
-- ------------------------------------------------------------
INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password_hash`, `role`) VALUES
(1, 'Canteen Administrator', 'admin@canteen.local', '9876543210', '$2y$10$lDHHUUSk.ZJJ7DzBZTHx4.1QtnjxKbQLQ1v6.9T99OvPrkdEmV5TG', 'admin'),
(2, 'Rahul Sharma', 'customer@canteen.local', '9812345678', '$2y$10$IInWt2zKbqICpTKKC6TlU.q9vdzqTc8B/Kf3.hDWx7i9GJ3TBgM2i', 'customer')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `phone`=VALUES(`phone`), `role`=VALUES(`role`);

-- ------------------------------------------------------------
-- 3. Insert Food Items
-- ------------------------------------------------------------
INSERT INTO `food_items` (`id`, `category_id`, `name`, `description`, `price`, `image`, `is_available`) VALUES
-- Breakfast
(1, 1, 'Masala Dosa', 'Crispy golden crepe stuffed with spiced potato mash, served with sambar and coconut chutney', 60.00, 'masala-dosa.jpg', 1),
(2, 1, 'Idli Vada Combo', 'Two steamed fluffy idlis and one crispy medu vada with aromatic sambar and chutneys', 50.00, 'idli-vada.jpg', 1),
(3, 1, 'Poori Bhaji', 'Three puffed golden pooris served with flavorful potato curry and mixed pickle', 55.00, 'poori-bhaji.jpg', 1),
(4, 1, 'Paneer Paratha', 'Stuffed whole-wheat flatbread with spiced paneer, served with fresh curd and butter', 70.00, 'paneer-paratha.jpg', 1),
(5, 1, 'Indori Poha', 'Steamed beaten rice tempered with mustard, curry leaves, crunchy peanuts, and sev', 40.00, 'poha.jpg', 1),

-- Meals
(6, 2, 'Special Veg Thali', 'Complete meal with 2 rotis, paneer gravy, seasonal veg, dal tadka, jeera rice, sweet, and papad', 120.00, 'veg-thali.jpg', 1),
(7, 2, 'Chicken Dum Biryani', 'Fragrant basmati rice layered with spiced marinated chicken and saffron, served with raita and salan', 180.00, 'chicken-biryani.jpg', 1),
(8, 2, 'Paneer Butter Masala with Naan', 'Rich cottage cheese in creamy tomato-butter gravy with two butter garlic naans', 140.00, 'paneer-butter-masala.jpg', 1),
(9, 2, 'Dal Khichdi Tadka', 'Comforting slow-cooked lentil and rice tempered with pure desi ghee and cumin', 90.00, 'dal-khichdi.jpg', 1),
(10, 2, 'Veg Fried Rice with Manchurian', 'Wok-tossed aromatic rice paired with crispy vegetable manchurian balls in gravy', 130.00, 'fried-rice-manchurian.jpg', 1),

-- Snacks
(11, 3, 'Crispy Samosa (2 Pcs)', 'Flaky pastry filled with spiced potatoes and green peas, served with sweet and mint chutneys', 30.00, 'samosa.jpg', 1),
(12, 3, 'Veg Cheese Grilled Sandwich', 'Toasted triple-layer sandwich packed with cucumbers, tomatoes, bell peppers, and melted cheese', 65.00, 'grilled-sandwich.jpg', 1),
(13, 3, 'Mumbai Pav Bhaji', 'Spiced buttery mashed vegetable curry served hot with two toasted butter pav buns and lemon wedges', 80.00, 'pav-bhaji.jpg', 1),
(14, 3, 'Crispy French Fries', 'Deep-fried salted potato fingers served with tangy tomato ketchup and mayonnaise', 70.00, 'french-fries.jpg', 1),
(15, 3, 'Paneer Bread Pakora', 'Crispy batter-fried bread stuffed with seasoned paneer slab and green chutney', 45.00, 'bread-pakora.jpg', 1),

-- Beverages
(16, 4, 'Desi Masala Chai', 'Strong brewed Assam tea infused with crushed ginger, cardamom, and aromatic spices', 20.00, 'masala-chai.jpg', 1),
(17, 4, 'South Indian Filter Coffee', 'Freshly brewed decoction with frothed hot milk, served in traditional brass dabarah and tumbler', 25.00, 'filter-coffee.jpg', 1),
(18, 4, 'Cold Coffee with Ice Cream', 'Chilled espresso blended with thick milk and topped with a scoop of vanilla ice cream', 60.00, 'cold-coffee.jpg', 1),
(19, 4, 'Sweet Mango Lassi', 'Rich and creamy yogurt cooler blended with Alphonso mango pulp and topped with pistachios', 50.00, 'mango-lassi.jpg', 1),
(20, 4, 'Fresh Lime Soda', 'Refreshing chilled sparkling soda with freshly squeezed lemon juice, mint, and rock salt', 40.00, 'lime-soda.jpg', 1),

-- Desserts
(21, 5, 'Gulab Jamun (2 Pcs)', 'Warm, soft milk-solid dumplings soaked in rose and cardamom-scented sugar syrup', 40.00, 'gulab-jamun.jpg', 1),
(22, 5, 'Rasmalai (2 Pcs)', 'Delicate cottage cheese patties soaked in saffron and cardamom flavored clotted milk', 60.00, 'rasmalai.jpg', 1),
(23, 5, 'Chocolate Brownie with Fudge', 'Warm chocolate fudge brownie loaded with walnuts and drizzled with dark chocolate syrup', 75.00, 'brownie.jpg', 1)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `price`=VALUES(`price`), `description`=VALUES(`description`);

-- ------------------------------------------------------------
-- 4. Insert Initial Sample Orders for Demonstration
-- ------------------------------------------------------------
INSERT INTO `orders` (`id`, `order_number`, `user_id`, `customer_name`, `customer_phone`, `total_amount`, `payment_method`, `payment_status`, `order_status`, `special_instructions`, `created_at`) VALUES
(1, 'ORD-20261021-001', 2, 'Rahul Sharma', '9812345678', 140.00, 'cash', 'paid', 'Completed', 'Extra chutney please', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(2, 'ORD-20261021-002', 2, 'Rahul Sharma', '9812345678', 190.00, 'cash', 'pending', 'Preparing', 'Make biryani mild spicy', DATE_SUB(NOW(), INTERVAL 30 MINUTE))
ON DUPLICATE KEY UPDATE `order_status`=VALUES(`order_status`);

-- ------------------------------------------------------------
-- 5. Insert Sample Order Items
-- ------------------------------------------------------------
INSERT INTO `order_items` (`id`, `order_id`, `food_item_id`, `item_name`, `unit_price`, `quantity`, `subtotal`) VALUES
(1, 1, 1, 'Masala Dosa', 60.00, 1, 60.00),
(2, 1, 13, 'Mumbai Pav Bhaji', 80.00, 1, 80.00),
(3, 2, 7, 'Chicken Dum Biryani', 180.00, 1, 180.00),
(4, 2, 16, 'Desi Masala Chai', 20.00, 1, 20.00)
ON DUPLICATE KEY UPDATE `unit_price`=VALUES(`unit_price`);
