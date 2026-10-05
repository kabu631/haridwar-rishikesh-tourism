<?php

/*
|--------------------------------------------------------------------------
| Trip Assistant (chat widget)
|--------------------------------------------------------------------------
|
| A guided chat in the corner of every page. Each option suggests published
| tour packages: a package belongs to an option when its section is listed
| in "sections" or its URL contains one of the "keywords", and none of the
| "exclude" fragments. "url" is the page behind the "See all" link.
|
*/

return [

    'greeting' => 'Namaste! 🙏 I can suggest the right tour in a couple of taps. What kind of trip are you planning?',

    // Packages returned per option (the chat shows the first few).
    'limit' => 12,

    'topics' => [
        [
            'key' => 'haridwar-rishikesh',
            'label' => 'Haridwar & Rishikesh',
            'icon' => 'temple',
            'reply' => 'Ganga Aarti at Har Ki Pauri, Lakshman Jhula and Triveni Ghat – here are our most booked Haridwar Rishikesh tours:',
            'url' => '/tour-packages.html',
            'keywords' => ['haridwar-tour-package', 'rishikesh-tour-package', 'haridwar-rishikesh-tour', 'haridwar-with-'],
        ],
        [
            'key' => 'chardham',
            'label' => 'Char Dham Yatra',
            'icon' => 'diya',
            'reply' => 'Yamunotri, Gangotri, Kedarnath and Badrinath with a local Haridwar team. These Char Dham packages are a good start:',
            'url' => '/chardham-yatra-tour-packages.html',
            'keywords' => ['chardham', 'char-dham', 'do-dham', 'kedarnath', 'badrinath'],
        ],
        [
            'key' => 'adventure',
            'label' => 'Rafting & camping',
            'icon' => 'raft',
            'reply' => 'White water rafting on the Ganges and riverside camps in Shivpuri. Pick an adventure:',
            'url' => '/adventure-tourism.html',
            'sections' => ['adventure'],
            'keywords' => ['rafting', 'camping', 'adventure-tour'],
        ],
        [
            'key' => 'hills',
            'label' => 'Hills & snow',
            'icon' => 'mountain',
            'reply' => 'Auli’s ski slopes, the Chopta meadows and Mussoorie’s Mall Road. Here are our hill station tours:',
            'url' => '/tour-packages.html',
            'keywords' => ['mussoorie-tour', 'auli', 'chopta', 'mussoorie-rishikesh', 'nainital-tour'],
            'exclude' => ['-with-', 'trek'],
        ],
        [
            'key' => 'trekking',
            'label' => 'Himalayan treks',
            'icon' => 'route',
            'reply' => 'From easy weekend hikes to high Himalayan passes, led by experienced local guides:',
            'url' => '/trekking.html',
            'sections' => ['trekking'],
        ],
        [
            'key' => 'yoga',
            'label' => 'Yoga & spiritual',
            'icon' => 'yoga',
            'reply' => 'Yoga in the world capital of yoga, ashram stays and meditation caves. These tours fit a slower, spiritual trip:',
            'url' => '/tour-packages.html',
            'keywords' => ['yoga', 'meditation', 'ashram', 'temple-rickshaw'],
        ],
        [
            'key' => 'combined',
            'label' => 'Add Agra, Varanasi & more',
            'icon' => 'map',
            'reply' => 'Combine Haridwar and Rishikesh with the Golden Triangle, Varanasi, Ayodhya or the hills:',
            'url' => '/tour-packages.html',
            'keywords' => ['-with-', 'golden-triangle', 'ayodhya', 'dharamshala'],
            'exclude' => ['haridwar-with-'],
        ],
        [
            'key' => 'local',
            'label' => 'Local walks & day tours',
            'icon' => 'village',
            'reply' => 'Guided walks, rickshaw rides through old Haridwar, village visits and bike tours:',
            'url' => '/special-tour.html',
            'sections' => ['special'],
        ],
    ],

];
