<?php

/*
|--------------------------------------------------------------------------
| Homepage content
|--------------------------------------------------------------------------
|
| Copied verbatim from the legacy homepage so every keyword-bearing heading,
| paragraph and internal link that ranks today is kept. Stored on the home
| page record (pages.extra) and editable in Admin → Pages → Home.
|
*/

return [

    'hero' => [
        'eyebrow' => 'Haridwar Tours, Rishikesh Tours, Haridwar and Rishikesh Tours',
        'heading' => 'Haridwar Rishikesh Tour Packages & Travel Guide',
        'text' => 'India Easy Trip Pvt Ltd is one of the most reputed travel agents in Haridwar. We are providing tours in Uttarakhand since 1995. We provide all kind of tours in Haridwar Rishikesh and Uttarakhand.',
        'image' => '/haridwar-images/haridwar-ganga-aarti.jpg',
        'image_alt' => 'Ganga Aarti at Har Ki Pauri, Haridwar',
        'highlights' => ['Car Rental', 'Hotels', 'Packages', 'Pilgrimage', 'Adventures', 'Wildlife', 'Village Tours'],
        // The four slides of the legacy homepage slider (same images and text).
        'slides' => [
            ['image' => '/haridwar-images/haridwar.jpg', 'image_alt' => 'Har Ki Pauri ghat on the Ganges in Haridwar', 'eyebrow' => 'Haridwar Tours, Rishikesh Tours, Haridwar and Rishikesh Tours', 'title' => 'Haridwar Tour Packages', 'text' => 'Get Best Deals From Local Travel Agent', 'button_label' => 'View Haridwar packages', 'button_url' => '/haridwar-tour-package.html'],
            ['image' => '/haridwar-images/haridwar-ganga-aarti.jpg', 'image_alt' => 'Evening Ganga Aarti at Har Ki Pauri, Haridwar', 'eyebrow' => 'Since 1995', 'title' => 'India Easy Trip Pvt Ltd is one of the most reputed travel agents in Haridwar', 'text' => 'We are providing tours in Uttarakhand since 1995. We provide all kind of tours in Haridwar Rishikesh and Uttarakhand.', 'button_label' => 'About India Easy Trip', 'button_url' => '/about-us.html'],
            ['image' => '/rishikesh-images/rishikesh-lakshman-jhula.jpg', 'image_alt' => 'Lakshman Jhula bridge over the Ganges in Rishikesh', 'eyebrow' => 'Adventure Activities & Pilgrimage Tours', 'title' => 'Haridwar Rishikesh Sightseeing Tours', 'text' => 'Temples, ghats, ashrams, Ganga Aarti, rafting and camping in the holy cities of Haridwar and Rishikesh.', 'button_label' => 'Haridwar Rishikesh tour', 'button_url' => '/haridwar-rishikesh-tour.html'],
            ['image' => '/rishikesh-images/rishikesh.jpg', 'image_alt' => 'Rishikesh town on the banks of the Ganges', 'eyebrow' => 'Haridwar Rishikesh Adventures with Pilgrimage Tour Packages', 'title' => 'Haridwar & Rishikesh Pilgrimage Packages', 'text' => 'Experience the high qaulity services in one of the holiest places in India like Haridwar Rishikesh. Car Rental, Hotels, Packages, Pilgrimage, Adventures, Wildlife, Village Tours.', 'button_label' => 'All tour packages', 'button_url' => '/tour-packages.html'],
        ],
    ],

    'offer' => [
        'url' => 'https://www.chardhampackage.com/chardham-fixed-departure-from-delhi.html',
        'image' => '/images/fixed-departure.png',
        'alt' => 'char dham fixed departure tour from Delhi',
    ],

    'promos' => [
        ['url' => 'https://www.indiaeasytrip.com/india-tour-packages/trekking/amarnath-yatra-tour-package-from-srinagar-7n-8d', 'image' => 'https://www.chardhampackage.com/img/amarnath-yatra.jpg', 'alt' => 'Shri Amarnath Yatra Package 2026', 'width' => 1200, 'height' => 150],
    ],

    'latest' => [
        'heading' => 'Latest Update Haridwar Rishikesh Tourism',
        'items' => [
            ['url' => '/online-puja-booking-in-haridwar.html', 'title' => 'Book Online Pooja Haridwar', 'text' => 'There are many types of Pooja Ceremony in Haridwar like Pitra pooja etc.', 'icon' => 'diya'],
            ['url' => '/international-yoga-festival-rishikesh-2018.html', 'title' => 'International Yoga Festival', 'text' => 'Every year International Yoga Festival is organized in Rishikesh since 1999.', 'icon' => 'yoga'],
            ['url' => '/traditional-dinner-with-an-indian-family.html', 'title' => 'Taditional Dinner with India Family', 'text' => 'Experience a traditional Indian family dinner in Haridwar with us.', 'icon' => 'dinner'],
            ['url' => '/village-tour-for-01night-02days.html', 'title' => 'Kanatal New Village Tour', 'text' => 'Special tours provides opportunity to experience the Indian culture as well as day to day life of local people.', 'icon' => 'village'],
        ],
    ],

    'other' => [
        'heading' => 'Other Haridwar Rishikesh Tour Packages and Guide',
        'items' => [
            ['url' => '/air-safari-hot-ballon-paragliding-rishikesh.html', 'title' => 'Air Safari Hot Balloon', 'text' => 'Air Safari Adventure offer first time in Rishikesh provide by Haridwar Rishikesh Tourism.', 'icon' => 'balloon'],
            ['url' => '/chardham-yatra-package.html', 'title' => 'Chardham Packages', 'text' => 'Get the best Chardham Packages with competitive rates, divinity and spiritualism.', 'icon' => 'temple'],
            ['url' => '/mussoorie.html', 'title' => 'Mussoorie Tourism', 'text' => 'Mussoorie is one of the best hill station in India. Mussoorie city is situated in the region of the northern indian state Uttarakhand.', 'icon' => 'mountain'],
            ['url' => 'http://www.nainitalcorbetttourism.com/nainital-tourism.html', 'title' => 'Nainital Tourism', 'text' => "Nainital is a Himalayan resort town in the Kumaon region of India's Uttarakhand state, at an elevation of roughly 2,000m.", 'icon' => 'lake'],
        ],
    ],

    'haridwar' => [
        'heading' => 'Haridwar Tourism',
        'url' => '/haridwar-tourism.html',
        'items' => [
            ['url' => '/haridwar.html', 'image' => '/haridwar-images/haridwar-tourism.jpg', 'alt' => 'haridwar-tourism', 'label' => 'About Haridwar', 'subtitle' => 'Information About Haridwar.', 'title' => 'Haridwar', 'text' => 'Haridwar is city in Haridwar district of Uttarakhand state in India. Haridwar is located on the bank of the River Ganga. Every day hundreds on Hindu people travel to Haridwar to take bath in River Ganga here.'],
            ['url' => '/haridwar-attractions.html', 'image' => '/haridwar-images/haridwar-tourist-attraction.jpg', 'alt' => 'haridwar-tourist-attraction', 'label' => 'Haridwar Attractions', 'subtitle' => 'Information About Tourist Attractions.', 'title' => 'Haridwar Attractions', 'text' => "Haridwar is a sacred place where pilgrims come from across the world. It is among the most revered hindu pilgrimage centers of India which occupies a prized position in hearts of Hindus. The meaning of Haridwar is 'Gateway to God'."],
            ['url' => '/haridwar-tour-package.html', 'image' => '/haridwar-images/haridwar-tours.jpg', 'alt' => 'haridwar travel package', 'label' => 'Haridwar Tour Packages', 'subtitle' => 'Information About Haridwar Travel Packages', 'title' => 'Haridwar Tour Package', 'text' => 'We provide all kind of tour packages in Haridwar. You can visit temples and ashrams of Haridwar, you can want to visit Ganga Aarti at Har Ki Pauri, Visit Rajaji national park and view wild elphants.'],
            ['url' => '/rental-a-car-taxi-in-haridwar.html', 'image' => '/haridwar-images/haridwar-car-rentals.JPG', 'alt' => 'haridwar-car-rentals', 'label' => 'Haridwar Car Rentals', 'subtitle' => 'Information About Haridwar Car Rentals.', 'title' => 'Rent a Car in Haridwar', 'text' => 'At Haridwar we have our own fleet of cars. We have all kind of cars and all the cars are well maintained. Our drivers are professional and reliable.'],
            ['url' => '/ganga-aarti-haridwar.html', 'image' => '/haridwar-images/ganga-aarti-haridwar.jpg', 'alt' => 'ganga-aarti-haridwar', 'label' => 'Ganga Aarti Haridwar', 'subtitle' => 'Information about Ganga Aarti.', 'title' => 'Ganga Aarti Haridwar', 'text' => 'Ganga Aarti Hardwar is performed at Har-ki-Pauri, its is one of the famous rituals in India. It starts in the after sunset, with great crowds gathered in the region of both the banks of a canal that carries the waters of Ganges.'],
            ['url' => '/haridwar-hotels.html', 'image' => '/haridwar-images/haridwar-hotels.jpg', 'alt' => 'haridwar-hotels', 'label' => 'Haridwar Hotels', 'subtitle' => 'Information about Haridwar Hotels.', 'title' => 'Haridwar Hotels', 'text' => 'Get best rates and expert advise on Haridwar hotels. We have very special rates of all major Haridwar Hotels.'],
        ],
    ],

    'rishikesh' => [
        'heading' => 'Rishikesh Tourism',
        'url' => '/rishikesh-tourism.html',
        'items' => [
            ['url' => '/rishikesh.html', 'image' => '/rishikesh-images/rishikesh-tourism.jpg', 'alt' => 'rishikesh-tourism', 'label' => 'About Rishikesh', 'subtitle' => 'Information of Rishikesh History.', 'title' => 'Rishikesh', 'text' => 'Rishikesh is town in the Dehradun District of Uttarakhand state in India. Rishikesh has spectacular view of jungle-clad hills. Rishikesh has also confluence of River Ganges and Chandrabhaga. Rishikesh is also known as gateway to the Char Dham.'],
            ['url' => '/rishikesh-attractions-and-sightseeing.html', 'image' => '/rishikesh-images/rishikesh-attraction.jpg', 'alt' => 'rishikesh-attraction', 'label' => 'Rishikesh Attractions', 'subtitle' => 'Information of Tourist Attraction.', 'title' => 'Rishikesh Attractions', 'text' => 'Rishikesh is one of the most holy cities in India. It is to be found in the Dehradun district of Uttarakhand. Rishikesh sightseeing includes visiting famous the places in and around Rishikesh. Holy city of Rishikesh in Uttarakhand is home to various tourist attractions.'],
            ['url' => '/rishikesh-ashrams.html', 'image' => '/rishikesh-images/rishikesh-ashrams.jpg', 'alt' => 'rishikesh-ashrams', 'label' => 'Rishikesh Yoga Ashrams', 'subtitle' => 'Information of Rishikesh Ashrams.', 'title' => 'Rishikesh Ashrams', 'text' => 'Ashrams in Rishikesh provide with full residential facilities. These ashrams are well-known for the best of stay facilities. Food, lodging, laundry and other requirements are fulfilled in the ashrams. Most of the ashrams here do not charge anything for stay. They are run on donations made by pilgrims and visitors.'],
            ['url' => '/ganga-aarti-rishikesh.html', 'image' => '/rishikesh-images/ganga-aarti-rishikesh.jpg', 'alt' => 'ganga-aarti-rishikesh', 'label' => 'Ganga Aarti Rishikesh', 'subtitle' => 'Information of Ganga Aarti Rishikesh', 'title' => 'Ganga Aarti Rishikesh', 'text' => 'Ganga Aarati in Rishikesh is a contrast to it, held in a small place with a few people and is a comparatively quite affair. You can sit calmly in a corner and witness the ritual or join the crowds singing Bhajans with them.'],
            ['url' => '/rishikesh-hotels.html', 'image' => '/rishikesh-images/rishikesh-hotels.jpg', 'alt' => 'rishikesh-hotels', 'label' => 'Rishikesh Hotels', 'subtitle' => 'Information of Rishikesh Hotels.', 'title' => 'Rishikesh Hotels', 'text' => 'Get best deals and expert advise on Rishikesh. Get special discount on all major Rishikesh hotels.'],
            ['url' => '/rishikesh-temples.html', 'image' => '/rishikesh-images/rishikesh-temples.jpg', 'alt' => 'rishikesh-temples', 'label' => 'Rishikesh Temples', 'subtitle' => 'Information of Rishikesh Temples.', 'title' => 'Rishikesh Temples', 'text' => 'Temples are wide-ranging in each and every place of the Rishikesh city. The temples of rishikesh are highly revered. Rishikesh is full of countless small, big, new and very old temples, spread in the mean streets of the city.'],
        ],
    ],

    'reviews' => [
        'heading' => 'What Our Clients Say',
        'subheading' => 'Guest Reviews on TripAdvisor',
        'badge' => '/images/trip-advisor-badge-haridwar-rishikesh-tourism.png',
        'badge_alt' => 'trip-advisor-badge-haridwar-rishikesh-tourism',
        'url' => 'http://www.tripadvisor.in/Attraction_Review-g616028-d4868270-Reviews-Haridwar_Rishikesh_Tourism_Private_Day_Tours-Haridwar_Uttarakhand.html',
        'cta' => 'Write a Review',
    ],

    'packages' => [
        'heading' => 'Haridwar Rishikesh Tourism - Tour Packages',
        'items' => [
            ['url' => '/haridwar-tour-package.html', 'image' => '/tour-packages/haridwar-tour-package.png', 'alt' => 'haridwar-tour-package', 'title' => 'Haridwar Tour Packages', 'text' => 'Haridwar tour package provides opportunity to experience the holy river Ganges, sacred bathing ghats, ancient temples, ashrams, hymns of priest and various holy rituals by Indian pilgrims. Haridwar is truly kaleidoscope to culture of India. Haridwar literally means Gateway to God. Haridwar is one of the most famous holy cities in India.'],
            ['url' => '/rishikesh-tour-package.html', 'image' => '/tour-packages/rishikesh-tour-package.png', 'alt' => 'rishikesh-tour-package', 'title' => 'Rishikesh Tour Packages', 'text' => 'Rishikesh Tour Packages includes travel in the city of Rishikeshand covers the popular tourist spots in the city including Ashrams, temples, markets, Ganges ceremony (Ganga Arti), rafting, camping, wildlife, waterfalls and much more. Rishikesh is a small city in the state Uttarakhand of northern India.'],
            ['url' => '/haridwar-rishikesh-tour.html', 'image' => '/tour-packages/haridwar-rishikesh-tour-package.png', 'alt' => 'haridwar-rishikesh-tour-package', 'title' => 'Haridwar Rishikesh Tour', 'text' => 'Haridwar Rishikesh Tour includes the tourist spots are asharms, temples, Har ki Paudi, himalayas view, Ganga aarti,Rafting, camping, wildlife tour and much more attraction. Haridwar city positioned in the foothills of the Shivalik, represents the point where the Ganga reaches the plains. Rishikesh is virtually a town of saints, sages and scholars.'],
            ['url' => '/haridwar-rishikesh-with-mussoorie-tour.html', 'image' => '/tour-packages/haridwar-rishikesh-with-mussoorie-tour-package.png', 'alt' => 'haridwar-rishikesh-with-mussoorie-tour-package', 'title' => 'Haridwar Rishikesh with Mussoorie Tour', 'text' => 'Haridwar Rishikesh with Mussoorie Tour include three city are Haridwar, Rishikesh and Mussoorie, a tour includes the tourist spots are temples, wildlife, himalayan and much more. Mussoorie is beautiful hill station in Uttarakhand State. Mussoorie, to be found in the Garhwal hills.'],
            ['url' => '/haridwar-rishikesh-with-golden-triangle-tour.html', 'image' => '/tour-packages/haridwar-rishikesh-with-golden-triangle-tour.png', 'alt' => 'haridwar-rishikesh-with-golden-triangle-tour', 'title' => 'Haridwar Rishikesh with Golden Triangle Tour', 'text' => 'Golden Triangle also takes you the holy cities of Haridwar and Rishikesh, to be found on the banks of River Ganga. The calm of the surroundings, spirituality on the banks, religious fervor in the temples, transcendental experiences in the Ashrams, and captivating spectacle of the evening Ganga Aarti.'],
            ['url' => '/haridwar-rishikesh-with-auli-tour.html', 'image' => '/tour-packages/haridwar-rishikesh-with-auli-tour.png', 'alt' => 'haridwar-rishikesh-with-auli-tour', 'title' => 'Haridwar Rishikesh with Auli Tour', 'text' => 'Haridwar Rishikesh with Auli Tour include three spots are Haridwar, Rishikesh and Auli, a tour includes the famouse tourist spots are asharms, temples, wildlife tour, markets, Ski Slopes and much more attraction. There are other interesting tourist attractions in Auli. The Gurso Bugyal is a vast meadow spread among forests of Oak and other trees.'],
        ],
    ],

    'guide' => [
        'heading' => 'Haridwar Rishikesh Tourism - Guide',
        'items' => [
            ['url' => '/haridwar-tourism.html', 'title' => 'Haridwar Tourism', 'text' => 'Haridwar has fascinated people from all over the world with her secularism and her traditions.', 'icon' => 'temple'],
            ['url' => '/rishikesh-tourism.html', 'title' => 'Rishikesh Tourism', 'text' => 'Rishikesh Tourism offers you an opening to experience the quietness and peace of mind that the place has to offer.', 'icon' => 'bridge'],
            ['url' => '/adventure-tourism.html', 'title' => 'Adventure Tourism', 'text' => 'Rishikesh is measured to be well-known for its white river rafting because it is the start of the river Gangers.', 'icon' => 'raft'],
            ['url' => '/special-tour.html', 'title' => 'Special Tours', 'text' => 'Special tours provides opportunity to experience the Indian culture as well as day to day life of local people.', 'icon' => 'village'],
            ['url' => '/tour-packages.html', 'title' => 'Tour Packages', 'text' => 'Haridwar Rishikesh Tourism provides entire Haridwar Rishikesh tour packages.', 'icon' => 'map'],
            ['url' => '/trekking.html', 'title' => 'Trekking Tours', 'html' => 'Trekking in the Himalayas is pleasant and can be finished with families and kids. Book <a href="https://indiaeasytrip.com/auli-chopta-tour-package.html" target="_blank" rel="noopener">Auli Chopta Tour Package</a>', 'text' => 'Trekking in the Himalayas is pleasant and can be finished with families and kids. Book Auli Chopta Tour Package', 'icon' => 'mountain'],
        ],
    ],

    'note' => '<strong>Note:</strong> Char Dham Yatra 2026 will start in Uttarakhand on 30 April 2026 on opening the doors of Yamunotri, Gangotri, and <a href="https://www.chardhampackage.com/kedarnath-tour.html" target="_blank" rel="noopener">Kedarnath</a> temples. Book the <a href="https://www.chardhampackage.com/" target="_blank" rel="noopener">Chardham Tour Package</a> and <a href="https://www.chardhampackage.com/chardham-helicopter-services.html" target="_blank" rel="noopener">Chardham Helicopter</a> Service From Dehradun.',

    'about' => [
        'heading' => 'India Easy Trip Pvt Ltd',
        'image' => '/images/india-easy-trip.jpg',
        'image_alt' => 'india-easy-trip',
        'paragraphs' => [
            'India Easy Trip Pvt Ltd is one of the leading travel agent in Haridwar. We are providing travel related services in Haridwar Rishikesh and Uttarakhand since march 1995. India Easy Trip Pvt Ltd is one of the oldest and reputed travel agents in Haridwar. We aim at promoting Haridwar Rishikesh and Uttrakhand in India by providing a tour packages in haridwar and rishikesh as well as all uttarakhand. tour packages and rishikesh tour packages. Our main focus is to make your tours memorable and enjoyable. With 20 years of experience, our company can offer all kind of travel related services such as local car rental in Haridwar Rishikesh, Car Rental for Packages in Uttarakhand, Guide Services, Sightseeing Tours in Haridwar Rishikesh, Package tours, Plgirmage Tours, Yoga and Meditation Tours, Adventure Tours, Wildlife Tours, <a href="https://indiaeasytrip.com/india-tour-packages/trekking/" target="_blank" rel="noopener">Trekking Tours</a>, Rafting and Camping Tours, Chardham Package Tours, <a href="https://www.indiaeasytrip.com/india-tour-packages/chardham-yatra-by-helicopter/" target="_blank" rel="nofollow noopener">Chardham Helicopter</a>, Honeymoon Tours, Village Tours and every other kind of tours. We can also provide air tickets, train tickets, money exchange,',
            'Haridwar Rishikesh Tourism is our intiative as a travel agent to provide complete travel and tour related information of Haridwar Rishikesh. You will get all kind of tour packages of Haridwar Rishikesh on this website. For more details on Haridwar Tour visit <a href="/haridwar.html">Haridwar Tourism</a>, and for Rishikesh tour information visit <a href="/rishikesh.html">Rishikesh Tourism</a>.',
            'Haridwar is a most important pilgrimage destination for Hindu people. Haridwar is also one of the leading tourist destination in Uttrakhand. It is one of the seven holiest places for Hindus. Rishikesh is a holy city occupying a great place in the hearts of the Hindus. It is sited at the foothills of the Himalayas at the meeting of the holy rivers Ganga and Chandrabhaga.',
        ],
    ],

];
