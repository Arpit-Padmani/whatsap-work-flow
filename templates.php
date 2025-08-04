<?php

$welcomeTemplate = [
    "messaging_product" => "whatsapp",
    "to" => $phone_number,
    "type" => "text",
    "text" => [
        "body" => "Hi 👋 Welcome to *Lorence Vitrified* Official Account!"
    ]
];
$askusername = [
    "messaging_product" => "whatsapp",
    "to" => $phone_number,
    "type" => "text",
    "text" => [
        "body" => "It's great to have you here! Before we get started, may I know your name, please?

(E.g. Ram Sharma)"
    ]
];

$inqueryTemplate = [
    "messaging_product" => "whatsapp",
    "to" => $phone_number,
    "type" => "interactive",
    "interactive" => [
        "type" => "list",
        "header" => [
            "type" => "text",
            "text" => ""
        ],
        "body" => [
            "text" => "Dear {{user_name}},\n\nHow may I assist you today? Please choose from the following options 👇"
        ],
        "footer" => [
            "text" => ""
        ],
        "action" => [
            "button" => "Choose Inquiry",
           "sections" => [
    [
        "title" => "Get in Touch",
        "rows" => [
            [
                "id" => "product_inquiry",
                "title" => "Product Enquiry 🛍️",
                "description" => ""
            ],
            [
                "id" => "dealership_inquiry",
                "title" => "Dealership Enquiry 🏢",
                "description" => ""
            ],
            [
                "id" => "exportImport_inqiry",
                "title" => "Export Enquiry 🌎",
                "description" => ""
            ],
            [
                "id" => "request_call_back",
                "title" => "Request a Callback 📞",
                "description" => ""
            ]
        ]
    ]
]

        ]
    ]
];
//Products / Tiles Inquiry
$tilesSelectionTemplate = [
    "messaging_product" => "whatsapp",
    "to" => $phone_number,
    "type" => "interactive",
    "interactive" => [
        "type" => "list",
        "header" => [
            "type" => "text",
            "text" => ""
        ],
        "body" => [
            "text" => "Please choose how you'd like to browse our products 👇"
        ],
        "footer" => [
            "text" => ""
        ],
        "action" => [
            "button" => "Browse Options",
            "sections" => [
                [
                    "title" => "Choose a Category",
                    "rows" => [
                        [
                            "id" => "search_by_area",
                            "title" => "By Area",
                            "description" => ""
                        ],
                        [
                            "id" => "search_by_size",
                            "title" => "By Size",
                            "description" => ""
                        ],
                        [
                            "id" => "search_by_surface",
                            "title" => "By Surface",
                            "description" => ""
                        ],
                        [
                            "id" => "search_by_look",
                            "title" => "By Look",
                            "description" => ""
                        ]
                    ]
                ]
            ]
        ]
    ]
];

$search_by_area = [
    "messaging_product" => "whatsapp",
    "to" => $phone_number,
    "type" => "interactive",
    "interactive" => [
        "type" => "list",
        "header" => [
            "type" => "text",
            "text" => "By Area"
        ],
        "body" => [
            "text" => "Great! 👌\nPlease select the area where you plan to use the tiles:"
        ],
        "footer" => [
            "text" => ""
        ],
        "action" => [
            "button" => "Choose Area",
            "sections" => [
                [
                    "title" => "Search Options",
                    "rows" => [
                        [ "id" => "Living Room", "title" => "Living Room", "description" => "Living Room" ],
                        [ "id" => "Bathroom", "title" => "Bathroom", "description" => "Bathroom" ],
                        [ "id" => "Bedroom", "title" => "Bedroom", "description" => "Bedroom" ],
                        [ "id" => "Kitchen", "title" => "Kitchen", "description" => "Kitchen" ],
                        [ "id" => "Balcony", "title" => "Balcony", "description" => "Balcony" ],
                        [ "id" => "Outdoor", "title" => "Outdoor", "description" => "Outdoor" ]
                    ]
                ]
            ]
        ]
    ]
];

$search_by_size = [
    "messaging_product" => "whatsapp",
    "to" => $phone_number,
    "type" => "interactive",
    "interactive" => [
        "type" => "list",
        "header" => [
            "type" => "text",
            "text" => "By Size"
        ],
        "body" => [
            "text" => "Awesome! 📏\nChoose your preferred tile size below:"
        ],
        "footer" => [
            "text" => ""
        ],
        "action" => [
            "button" => "Choose Size",
            "sections" => [
                [
                    "title" => "Search Options",
                    "rows" => [
                        [ "id" => "20X120CM", "title" => "20X120 CM", "description" => "20X120 CM" ],
                        [ "id" => "30X60CM", "title" => "30X60 CM", "description" => "30X60 CM" ],
                        [ "id" => "40X80CM", "title" => "40X80 CM", "description" => "40X80 CM" ],
                        [ "id" => "60X120CM", "title" => "60X120 CM", "description" => "60X120 CM" ],
                        [ "id" => "60X60CM", "title" => "60X60 CM", "description" => "60X60 CM" ],
                        [ "id" => "80X80CM", "title" => "80X80 CM", "description" => "80X80 CM" ]
                    ]
                ]
            ]
        ]
    ]
];

$search_by_surface = [
    "messaging_product" => "whatsapp",
    "to" => $phone_number,
    "type" => "interactive",
    "interactive" => [
        "type" => "list",
        "header" => [
            "type" => "text",
            "text" => "By Surface"
        ],
        "body" => [
            "text" => "Got it! ✨\nWhat type of tile finish are you looking for?"
        ],
        "footer" => [
            "text" => ""
        ],
        "action" => [
            "button" => "Choose Surface",
            "sections" => [
                [
                    "title" => "Search Options",
                    "rows" => [
                        [ "id" => "glit", "title" => "Glit", "description" => "Glit" ],
                        [ "id" => "glossy", "title" => "Glossy", "description" => "Glossy" ],
                        [ "id" => "matt", "title" => "Matt", "description" => "Matt" ],
                        [ "id" => "matte_x", "title" => "Matte-X", "description" => "Matte-X" ],
                        [ "id" => "shine_structured", "title" => "Shine Structured", "description" => "Shine Structured" ],
                        [ "id" => "structured", "title" => "Structured", "description" => "Structured" ],
                        [ "id" => "textured_matt", "title" => "Textured Matt", "description" => "Textured Matt" ]
                    ]
                ]
            ]
        ]
    ]
];

$search_by_look = [
    "messaging_product" => "whatsapp",
    "to" => $phone_number,
    "type" => "interactive",
    "interactive" => [
        "type" => "list",
        "header" => [
            "type" => "text",
            "text" => "Look & Feel"
        ],
        "body" => [
            "text" => "Perfect! 🎨\nSelect the style or look you’d like for your tiles:"
        ],
        "footer" => [
            "text" => ""
        ],
        "action" => [
            "button" => "Choose Style",
            "sections" => [
                [
                    "title" => "Search Options",
                    "rows" => [
                        [ "id" => "Concrete", "title" => "Concrete", "description" => "" ],
                        [ "id" => "Decorative", "title" => "Decorative", "description" => "" ],
                        [ "id" => "Marble", "title" => "Marble", "description" => "" ],
                        [ "id" => "Rustic", "title" => "Rustic", "description" => "" ],
                        [ "id" => "Solid", "title" => "Solid", "description" => "" ],
                        [ "id" => "Stone", "title" => "Stone", "description" => "" ],
                        [ "id" => "Wood", "title" => "Wood", "description" => "" ]
                    ]
                ]
            ]
        ]
    ]
];

$ask_squarefeet = [
    "messaging_product" => "whatsapp",
    "to" => $phone_number,
    "type" => "text",
    "text" => [
        "body" => "Got it! \n\nPlease enter the required area (in square feet):"
    ]
];

$thankyou = [
    "messaging_product" => "whatsapp",
    "to" => $phone_number,
    "type" => "text",
    "text" => [
        "body" => "Thank you! 🙌\nOne of our experts will contact you shortly.\nExplore more: https://lorencesurfaces.com"
    ]
];

// Delarship templates
$askCompanyName = [
    "messaging_product" => "whatsapp",
    "to" => $phone_number,
    "type" => "text",
    "text" => [
        "body" => "Thank you! 🙏\nCould you please share your *Firm or Company Name*?"
    ]
];

$askOtherSupplier = [
    "messaging_product" => "whatsapp",
    "to" => $phone_number,
    "type" => "text",
    "text" => [
        "body" => "Are you sourcing from any other tile supplier?\nPlease mention the name if yes. 😊"
    ]
];

$askOnboardTiming = [
    "messaging_product" => "whatsapp",
    "to" => $phone_number,
    "type" => "interactive",
    "interactive" => [
        "type" => "button",
        "body" => [
            "text" => "When are you planning to onboard a new supplier?"
        ],
        "action" => [
            "buttons" => [
                [ "type" => "reply", "reply" => [ "id" => "onboard_immediate", "title" => "1️⃣ Immediate" ] ],
                [ "type" => "reply", "reply" => [ "id" => "onboard_later", "title" => "2️⃣ Later" ] ]
            ]
        ]
    ]
];

$dealershipThankYou = [
    "messaging_product" => "whatsapp",
    "to" => $phone_number,
    "type" => "text",
    "text" => [
        "body" => "Thanks for your interest! 🙌\nOur team will contact you soon.\nMeanwhile, explore: https://lorencesurfaces.com"
    ]
];

// Export/Import templates
$askCountry = [
    "messaging_product" => "whatsapp",
    "to" => $phone_number,
    "type" => "text",
    "text" => [
        "body" => "Awesome, thanks! 🌍\nWhich country are you importing from or exporting to?"
    ]
];

$askEmail = [
    "messaging_product" => "whatsapp",
    "to" => $phone_number,
    "type" => "text",
    "text" => [
        "body" => "Could you please share your email address? 📧"
    ]
];

$askBrands = [
    "messaging_product" => "whatsapp",
    "to" => $phone_number,
    "type" => "text",
    "text" => [
        "body" => "Are you currently working with any specific tile brands? Let us know! 🏷️"
    ]
];

$exportThankYou = [
    "messaging_product" => "whatsapp",
    "to" => $phone_number,
    "type" => "text",
    "text" => [
        "body" => "Thanks for sharing the details! 🙏\nOur export team will contact you soon.\nExplore our collection:\nhttps://lorencesurfaces.com"
    ]
];

// Error messages
$errorMessage = [
    "messaging_product" => "whatsapp",
    "to" => $phone_number,
    "type" => "text",
    "text" => [ "body" => "❌ Please enter a valid 6-digit pincode." ]
];

// Validation template
$invalid_option_try_again = [
    "messaging_product" => "whatsapp",
    "to" => $phone_number,
    "type" => "text",
    "text" => [ "body" => "❌ Invalid option. Please choose from the list above." ]
];

$invalid_response_prompt = [
    "messaging_product" => "whatsapp",
    "to" => $phone_number,
    "type" => "text",
    "text" => [ "body" => "⚠️ Please select from the provided options." ]
];

$invalidSquareFeetMessage = [
    "messaging_product" => "whatsapp",
    "to" => $phone_number,
    "type" => "text",
    "text" => [ "body" => "❗ Please enter a valid square feet value (e.g., 500 or 10x10)." ]
];

$invalid_interactive_response = [
    "messaging_product" => "whatsapp",
    "to" => $phone_number,
    "type" => "text",
    "text" => [ "body" => "⚠️ Please tap one of the buttons to continue." ]
];

$invalid_companyname_response = [
    "messaging_product" => "whatsapp",
    "to" => $phone_number,
    "type" => "text",
    "text" => [ "body" => "⚠️ That doesn’t look like a valid company name. Please try again." ]
];

$retryMessageCountry = [
    "messaging_product" => "whatsapp",
    "to" => $phone_number,
    "type" => "text",
    "text" => [ "body" => "❌ Please enter a valid country name like *India*, *USA*, etc." ]
];

$retryMessageEmail = [
    "messaging_product" => "whatsapp",
    "to" => $phone_number,
    "type" => "text",
    "text" => [ "body" => "❌ Please enter a valid email like *you@example.com*" ]
];

$retryMessageBrand = [
    "messaging_product" => "whatsapp",
    "to" => $phone_number,
    "type" => "text",
    "text" => [ "body" => "❌ Please enter a valid brand name." ]
];

$maximum_attempts_reached = [
    "messaging_product" => "whatsapp",
    "to" => $phone_number,
    "type" => "text",
    "text" => [ "body" => "🚫 You’ve reached the maximum number of attempts. Thank you for your time!" ]
];

$invalid_option_template = [
    "messaging_product" => "whatsapp",
    "to" => $phone_number,
    "type" => "text",
    "text" => [
        "body" => "❌ Invalid option. Please select from the list below 👇"
    ]
];
