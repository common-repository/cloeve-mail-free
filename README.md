# Cloeve Mail Free
Cloeve Mail Free is a free WordPress plugin to capture, build, and manage an email list. Seamless integration in your website with 2 easy steps. The plugin web page is [here](https://cloeve.com/tech/cloeve-mail-free-wordpress-plugin/).

## Installing
1. Download the plugin into your plugins directory
2. Enable in the WordPress admin

## Key Features
- Add email input forms to any pages or posts with the simple shortcode `[cloeve_mail]`
- Add as many input forms to a given page/post as you’d like, there is no limit
- View list of emails and the source page where they were captured right in the WordPress admin dashboard
- Export list of emails as a CSV at any time

## Shortcode Options
### type
This option is used to set the type of input form to display. There currently are 3 types:
- `[cloeve_mail type="1"]` is a full width, optional color background, padded centered inline form
- `[cloeve_mail type="2"]` is a full width form with a header title and paragraph
- default is a full width inline form

### height
This option is used to set the height in px of input form to display. Example: `[cloeve_mail height="45"]`

### bg_color
This option is used with `[cloeve_mail type="1"]` to set the background color surrounding the input form to display. Example: `[cloeve_mail type="1" bg_color="#ff0000"]`

### action_title
This option is used with `[cloeve_mail type="2"]` to set the header title above the input form to display. Example: `[cloeve_mail type="2" action_title="Subscribe"]`

### action_paragraph
This option is used with `[cloeve_mail type="2"]` to set the header paragraph above the input form to display. Example: `[cloeve_mail type="2" action_paragraph="Enter your email below to receive all the latest features, updates, and news!"]`

### placeholder
This option is used to set the placeholder text of input form to display. Example: `[cloeve_mail placeholder="Email address"]`

### btn_label
This option is used to set the text of input form button to display. Example: `[cloeve_mail btn_label="Subscribe"]`

### btn_color
This option is used to set the color of input form button to display. Example:`[cloeve_mail btn_color="#ff0000"]`

## Examples
- `[cloeve_mail type="1" bg_color="#ff0000"]`
- `[cloeve_mail type="2"]`
- `[cloeve_mail btn_color="#ff0000" btn_label="Get Started!" placeholder="Email Address"]`