# My Changa
Contributions/donations/funding plugin for WordPress - doesn't use WooCommerce

## Installation
Download from the release page and extract in the plugins directory of your WordPress installation. Activate the plugin and comfigure it appropriately.

## Configuration
Create an app on the safaricom developer Portal(Daraja) and innput the Consumer key and secret, as well as your shortcode/till/phone number. Register your callback URLs by clicking the link in the My Changa settings page.

## Usage
To render the contribution form, create a new post/page and use the following shortcode as the only HTML content - `[MCFORM]`

## Customization
The form can be customized with custom CSS styling of the various classes or IDs as follows
  <b>Form</b> `.mc_contribution_form` or `#mc-contribution-form`
  <b>Phone</b> `.mc_phone` or `#mc-phone`
  <b>Amount</b> `.mc_amount` or `#mc-amount`

## Contribution
If interested in contributing to the development of this plugin, please fork the project, do your magic and submit a pull request. I will review your changes and incorporate your changaes if they are alright.

## Licensing
This work is released under the MIT License, which grants you permission to use, share and modify the software at no additional cost, and for you to optionally grant the same right to others, subject to two conditions:

<li>I don't guarantee that the software doesn't work properly/perfectly.</li>
<li>I still own the copyright to their work, so you can't remove the license. If you modify the software, you should be clear about any changes you have made, and what license you are distributing those changes under.</li>
