<?php
$items = [
    'masala-dosa.jpg' => ['Masala Dosa', '#FF9800', 'Crispy Golden Crepe with Sambar'],
    'idli-vada.jpg' => ['Idli Vada Combo', '#FFA726', 'Steamed Fluffy Idlis & Medu Vada'],
    'poori-bhaji.jpg' => ['Poori Bhaji', '#FB8C00', 'Puffed Golden Pooris & Potato Curry'],
    'paneer-paratha.jpg' => ['Paneer Paratha', '#F57C00', 'Stuffed Flatbread with Curd & Butter'],
    'poha.jpg' => ['Indori Poha', '#FFB74D', 'Steamed Beaten Rice with Sev & Peanuts'],
    'veg-thali.jpg' => ['Special Veg Thali', '#43A047', 'Complete Royal Meal with Paneer & Rotis'],
    'chicken-biryani.jpg' => ['Chicken Biryani', '#E53935', 'Aromatic Dum Rice with Tender Chicken'],
    'paneer-butter-masala.jpg' => ['Paneer Butter Masala', '#FB8C00', 'Rich Creamy Gravy with Butter Naan'],
    'dal-khichdi.jpg' => ['Dal Khichdi Tadka', '#FBC02D', 'Comfort Lentil & Rice with Pure Ghee'],
    'fried-rice-manchurian.jpg' => ['Fried Rice & Manchurian', '#D81B60', 'Wok-Tossed Rice with Manchurian Balls'],
    'samosa.jpg' => ['Crispy Samosa (2 Pcs)', '#FF9800', 'Flaky Spiced Potato Pastry & Chutneys'],
    'grilled-sandwich.jpg' => ['Cheese Grilled Sandwich', '#7CB342', 'Toasted Veggies & Melted Cheese'],
    'pav-bhaji.jpg' => ['Mumbai Pav Bhaji', '#E53935', 'Buttery Spiced Mash & Warm Pav Buns'],
    'french-fries.jpg' => ['Crispy French Fries', '#FDD835', 'Golden Salted Fries with Dip'],
    'bread-pakora.jpg' => ['Paneer Bread Pakora', '#FB8C00', 'Batter-Fried Bread Stuffed with Paneer'],
    'masala-chai.jpg' => ['Desi Masala Chai', '#6D4C41', 'Ginger & Cardamom Spiced Assam Tea'],
    'filter-coffee.jpg' => ['South Indian Filter Coffee', '#5D4037', 'Freshly Brewed Strong Coffee Decoction'],
    'cold-coffee.jpg' => ['Cold Coffee with Ice Cream', '#8D6E63', 'Chilled Frothy Coffee with Vanilla Scoop'],
    'mango-lassi.jpg' => ['Sweet Mango Lassi', '#FFA000', 'Alphonso Mango & Thick Yogurt Cooler'],
    'lime-soda.jpg' => ['Fresh Lime Soda', '#7CB342', 'Chilled Sparkling Soda with Mint & Salt'],
    'gulab-jamun.jpg' => ['Gulab Jamun (2 Pcs)', '#8E24AA', 'Warm Rose & Cardamom Sugar Syrup Treats'],
    'rasmalai.jpg' => ['Rasmalai (2 Pcs)', '#FDD835', 'Delicate Patties in Saffron Clotted Milk'],
    'brownie.jpg' => ['Chocolate Brownie', '#3E2723', 'Walnut Fudge Delight with Warm Sauce']
];

$dir = __DIR__ . '/../assets/images/foods';
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

foreach ($items as $filename => $data) {
    [$title, $color, $desc] = $data;
    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 500 350" width="500" height="350">
  <defs>
    <linearGradient id="bgGrad_{$filename}" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="{$color}" stop-opacity="0.18"/>
      <stop offset="100%" stop-color="{$color}" stop-opacity="0.38"/>
    </linearGradient>
  </defs>
  <rect width="500" height="350" fill="url(#bgGrad_{$filename})"/>
  <circle cx="250" cy="140" r="85" fill="{$color}" opacity="0.15"/>
  <circle cx="250" cy="140" r="70" fill="#FFFFFF"/>
  <circle cx="250" cy="140" r="55" fill="{$color}" opacity="0.9"/>
  <!-- Cloche icon inside plate -->
  <path d="M215 150 C215 125 285 125 285 150 Z" fill="#FFFFFF"/>
  <rect x="205" y="152" width="90" height="6" rx="3" fill="#FFFFFF"/>
  <circle cx="250" cy="120" r="5" fill="#FFFFFF"/>
  <!-- Badge card -->
  <rect x="30" y="240" width="440" height="85" rx="12" fill="#FFFFFF" opacity="0.96"/>
  <text x="250" y="275" font-family="system-ui, -apple-system, sans-serif" font-size="20" font-weight="700" fill="#212529" text-anchor="middle">{$title}</text>
  <text x="250" y="303" font-family="system-ui, -apple-system, sans-serif" font-size="13" fill="#6c757d" text-anchor="middle">{$desc}</text>
</svg>
SVG;
    file_put_contents($dir . '/' . $filename, $svg);
}
echo "Generated " . count($items) . " sample food placeholder images successfully.\n";
