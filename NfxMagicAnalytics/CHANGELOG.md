# 4.1.2
- Fix: Profit calculation for net and tax-free orders
- Fix: Error in various statistics for orders with item quantity 0

# 4.1.1
- New: More data in Sales by Affiliate statistics: data for orders without a affiliate code
- New: More data in Sales by Campaign statistics: data for orders without a campaign
- Fix: Error in the statistics of the abandonment analysis
- Fix: Error in calculations in the Pickware ERP Pro return statistics

# 4.1.0
- New: 5 new statistics that take partial returns and refunds into account - but only when using Pickware ERP Pro Returns.
- New: Delivery address added to the table of related orders.
- New: Voucher statistics now include two overviews, grouped by promotion code and by discount campaign - better for individual codes.
- New: More data for product impression statistics - including the number of sales and conversion rate.
- New: Additional filter for product impressions to exclude partner codes - for example, to ignore imported marketplace orders.
- New: Additional filter for conversion statistics to exclude partner codes - for example, to ignore imported marketplace orders.
- New: In the statistics "To Run Out Of Stock", inactive products are excluded.
- New: Support for compressed payload column in the cart table for abandonment statistics.
- New: More data for "Customer By Turnover": Last login
- Fix: Error in profit calculation in the orders table.
- Fix: In "Sales by Categories," the category selection no longer closes after each change.
- Fix: Categories with assignment via a product stream in categories statistics

# 4.0.9
- New: CSV export of data from underlying orders
- New: CSV export of data from underlying products
- New: Profit Column for the underlying orders
- New: Orders by time of day now takes timezone in consideration
- New: Saving the invoice date for statistics by invoice date can now be disabled via the switch "Record additional order data if possible"
- New: Saving the invoice date now also works when the date is provided as an object instead of a string
- Fix: Corrected errors in the Y-axis values in some charts

# 4.0.8
- New: Products with low/high stock now without inactive products
- New: More data for Sales by Products statistics
- New: Manual link in action bar
- New: Additional filter in single-product-statistics: Sum up variants
- Fix: Cancellation analysis adapted to latest changes in shopware database table cart
- Fix: Columns in csv export of Sales by Product with variants summed up

# 4.0.7
- Fix: Error with the delivery states filter
- Fix: Error with wrong language id from administration

# 4.0.6
- Fix: Compability problem with the foreign key removal

# 4.0.5
- New: 1 new Statistics in category orders: Orders (quarterly)
- New: More filters for the statistics of the category By Invoice Date
- Fix: Error in Visitors referrer
- Fix: Foreign key constraint from invoice date table removed

# 4.0.4
- Fix: Error in the Age Distribution Statistics fixed
- New: Category Statistics By Invoice Date
- New: 4 new Statistics in category By Invoice Date:
- Orders (daily)
- Orders (monthly)
- Orders (quarterly)
- Sales by Billing Country
- New: 3 Statistics reworked for more data:
- Orders (daily)
- Orders (monthly)
- Sales by Products

# 4.0.3
- Fix: Fixed bugs in cancellation analysis

# 4.0.2
- Fix: Fixed bugs in cancellation analysis

# 4.0.1
- Fix: Fixed bug in cron job

# 4.0.0
- Adaptation to Shopware 6.6

# 3.0.8
- New: The statistics All Aborted Carts now includes product number and a link to the product detail page for the cart items

# 3.0.7
- Fix: Some minor bugs fixed

# 3.0.6
- Fix: Fixed: Problem with visitor counts of sales channels with subdirectories in the url
- New: Statistics sales by buyer type (guest/registered)

# 3.0.5
- New: IP Blacklist in Plugin Settings for Visitor Count
- New: Email column in customer sales statistics
- New: Dashboard statistics settings user dependent
- New: Favorite statistics user dependent selectable
- Fix: Collection of visitor data improved for SW 6.5
- New: Option to show parents instead of variant products with total data
- New: Hide option for products without sales in product profits statistics 
- New: New feature display ordered products: For some statistics, a link in the action column to an overview
  that displays the underlying products
- Fix: Currency bug in promotion statistics fixed
- New: Many more grids now sortable
- New: Additional acl permission for dashboard statistics, so rights for dashboard and statistics module can be assigned separately
- Fix: Customers online fixed

# 3.0.4
- Fix: Bug in variant und products with filter statistics fixed

# 3.0.3
- New: 2 new category statistics
- Fix: Minor bugs fixed

# 3.0.2
- Error in the statistics using the order delivery db table fixed

# 3.0.1
- New statistics in marketing: Conversion (daily), Conversion (monthly)
- New feature additional filters: New menu item in sidebar under options, not yet active for all statistics,
  leads to individual filters for the selected statistic, more of these filters will come 
- New feature display underlying orders: For some statistics, a link in the action column to an overview
  that displays the underlying orders, will be expanded to more statistics
- "Update" and "CSV Export" buttons moved to header
- Sales by Campaign and by Partner moved to marketing section
- Sorting by columns in selected statistics, more to come

# 3.0.0
- Adjustments to Shopware 6.5

# 2.3.8
- New Statistics: Sales By Currency
- Extension of the 3 statistics product impressions, manufacturer impressions, category impressions with:
- Distinction between registered and non-registered visitors 
- Consideration of the customer group filter

# 2.3.7
- Error in the statistics All Aborted Carts fixed

# 2.3.6
- Statistics All Aborted Carts adapted to the Shopware update
- 3 new statistics: Sales By Tax Rate, Sales By Salutation, Customers By Salutation
- Improvement of the visitor tracking
- Shopware ACL permissions system introduced: New permission Content -> Coolbax Statistics
  (to be found under Settings -> Users & permissions -> Roles -> Edit) 
  This must be set to at least View, otherwise no Statistics menu item and no statistics on the dashboard! 
  If Dashboard Statistics are to be edited, Coolbax Statistics must be set to Edit. 
  Users with administrator rights do not have to change anything.

# 2.3.5
- Bug fixes in promotion statistics
- Bug fixes for statistics options
- Bug fixes for responsiveness
- New Statistics: Current visitors and logins

# 2.3.4
- Bug fixes in statistics options, plugin setting
- Impressions write exceptions intercepted
- Performance improved

# 2.3.3
- Fixed a bug with category impressions counting

# 2.3.2
- New global filter for customer groups
- Improvement of the First Time Orders statistics
- More settings for csv export added

# 2.3.1
- Improvement of the date displays
- Improvement of the data collection

# 2.3.0
- New statistics Cross-selling added under Products

# 2.2.9
- Bug fixing for dashboard statistics

# 2.2.8
- Improvements and bug fixing for dashboard statistics
- Visitors und clicks added to quick overview 

# 2.2.7
- Improved name display for variants
- Fixed compatibility problem with SW <= 6.4.5 when selecting the dates
- More predefined date ranges

# 2.2.6
- Aborted carts improved with display of the products
- Action code for promotion statistics added
- New Statistics: Lexicon Impressions
- New Statistics: Single-product statistics

# 2.2.5
- Bug with the visitors calculation fixed

# 2.2.4
- First time orders statistics improved

# 2.2.3
- Bug with the visitors calculation fixed
- First time orders statistics improved

# 2.2.2
- Error solved with products without manufacturer

# 2.2.1
- New statistics for Product Impressions, Category Impressions, Manufacturer Impressions, Page Impressions, Visitors

# 2.2.0
- Fix for chart mouseover display errors

# 2.1.9
- Total display in the Quick Overview improved

# 2.1.8
- Adjustments for extension plugins

# 2.1.7
- Bug in the first-time-order statistics fixed
- Improvement for the unfinished orders statistics

# 2.1.6
- Performance improvement
- Chart type preselection in plugin configuration now applied to dashboard, too

# 2.1.5
- New feature: Option to show selected statistics on the dashboard
- Smaller error fixes
- New statistics
- New colum in quick overview to show the daily number of first time orders

# 2.1.4
- Adding options to select order transaction status and order delivery status to exclude from calculations
- New colum in quick overview to show how many of the new customers of the day have made at least one order
- Adding option to preselect the chart type for statistics with chart type selection

# 2.1.3
- Fixed a bug in some statistics with the end date

# 2.1.2
- Improvements to the data savings of orders (device, os, browser)

# 2.1.1
- Bug fixed in Sales by Payment Statistic
- Improvements for timezone bugs

# 2.1.0
- Adjustments to the new route for the link in the promotion statistics
- Improvements of the handling of the date values

# 2.0.9
- Improvements for the product profits statistics 

# 2.0.8
- Bug fixed in Browser, Device and OS Statistics

# 2.0.7
- Bugs fixed: In search with empty term, timezone problems, manufacturer statistic problem with products without a manufacturer

# 2.0.6
- Bug in line items with wrong orderVersionId bypassed

# 2.0.5
- Fixed a bug in the csv export for the sales by product statistics

# 2.0.4
- Changes to some snippets
- Changes to the csv export - use of SW Filesystem for temporary storage
- Csv separator selectable in config

# 2.0.3
- Bugs in manufacturer and payment statistics fixed
- Performance improvement

# 2.0.2
- Two minor bugs corrected
- Snippet product stream changed to dynamic product group

# 2.0.1
- 9 new statistics added (search, devices, variants, product profit, ...)
- Starting to collect search request data

# 2.0.0
- Adjustments for Shopware 6.4 Update

# 1.0.7
- Improvement of the display of names of variant products

# 1.0.6
- Changes to the creation of the csv export path
- Changes to the csv export and download
- Adding gross/net option
- Adding option to select order status to exclude from calculations
- Improvement of promotion statistics calculations

# 1.0.5
- Getting the plugin ready for extensions

# 1.0.4
- Correction for setting default configs during activation process

# 1.0.3
- Correction when reading the date filter for certain server settings

# 1.0.2
- CSV Export of the statistics data added
- Bar charts added as new option
- Navigation tree overhaul, introduction of groups
- Many new statistics
- Expanding of old statistics

# 1.0.1
- Error with names of variants fixed
- New column Product Number in tables with products

# 1.0.0
- First version of the Plugin for Shopware 6
