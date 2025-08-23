# PetVet Articles System

A comprehensive article management system for veterinary clinics, allowing doctors and administrators to share knowledge with pet owners.

## 🚀 Features

### For Everyone (Public Access)
- **Browse Articles**: View all published articles with rich content
- **Search & Filter**: Find articles by topic, keywords, or content
- **Sort Options**: Sort by latest, oldest, most popular, or alphabetically
- **Read Articles**: Full article viewing with proper formatting
- **View Statistics**: See view counts and comment numbers
- **View Inquiries**: Browse customer inquiries and their status

### For Logged-in Users
- **Comment System**: Add comments to articles (requires login)
- **User Profiles**: Comments show user type (Pet Owner, Doctor, Staff, Admin)

### For Doctors, Staff & Administrators
- **Create Articles**: Rich text editor with TinyMCE
- **Edit Articles**: Modify existing articles (staff/admin can edit any, doctors can edit own)
- **Topic Management**: Predefined topics for consistency
- **Tag System**: Add relevant tags for better discoverability
- **Content Management**: Full CRUD operations on articles

### Advanced Features
- **Rich Text Editing**: Professional content creation with TinyMCE
- **Topic Categorization**: Organized content by veterinary topics
- **Tag System**: Flexible tagging for better search
- **View Tracking**: Monitor article popularity
- **Comment Moderation**: Delete inappropriate comments
- **Responsive Design**: Mobile-friendly interface

## 📁 File Structure

```
articles/
├── index.php          # Main articles dashboard
├── post.php          # Article creation page
├── view.php          # Article viewing with comments
├── edit.php          # Article editing page
├── setup_articles.sql # Database setup script
└── README.md         # This file
```

## 🗄️ Database Schema

### Articles Table
- `article_id` - Unique identifier
- `title` - Article title
- `summary` - Brief description
- `content` - Full article content (HTML)
- `topic` - Categorized topic
- `tags` - Comma-separated tags
- `author_id` - Author's user ID
- `author_type` - 'admin' or 'doctor'
- `created_at` - Publication date
- `updated_at` - Last modification date
- `view_count` - Number of views
- `status` - 'published', 'draft', or 'archived'

### Article Comments Table
- `comment_id` - Unique identifier
- `article_id` - Associated article
- `commenter_id` - Commenter's user ID
- `commenter_type` - User type (admin, doctor, user, staff)
- `commenter_name` - Display name
- `comment_text` - Comment content
- `created_at` - Comment date
- `status` - 'active', 'hidden', or 'deleted'

## 🛠️ Installation

1. **Database Setup**
   ```sql
   -- Run the setup script
   source articles/setup_articles.sql;
   ```

2. **File Permissions**
   - Ensure the `articles/` folder is accessible
   - Verify database connection in `../config.php`

3. **TinyMCE Setup**
   - The system uses TinyMCE CDN (free tier)
   - For production, consider getting an API key

## 🔐 Access Control

### Public Access
- Article browsing and reading
- Search and filtering
- View article statistics

### User Access (Login Required)
- Submit comments on articles
- View user-specific content
- Submit new inquiries to veterinary staff
- Track inquiry status and responses

### Doctor Access
- Create new articles
- Edit own articles
- Delete comments on own articles
- Full article management

### Staff Access
- Create new articles
- Edit any article
- Reply to customer inquiries
- Manage inquiry statuses
- Full article management

### Admin Access
- Create articles
- Edit any article
- Delete any comment
- Reply to customer inquiries
- Manage inquiry statuses
- Full system control

## 🎨 User Interface

### Design Features
- **Responsive Layout**: Works on all device sizes
- **Modern UI**: Clean, professional veterinary clinic aesthetic
- **Color Scheme**: Consistent with PetVet branding
- **Typography**: Readable fonts and proper spacing
- **Interactive Elements**: Hover effects and smooth transitions

### Navigation
- **Header Navigation**: Easy access to all sections
- **Breadcrumb Navigation**: Clear page hierarchy
- **Action Buttons**: Prominent call-to-action elements
- **Contextual Menus**: Role-based action availability

## 📱 Responsive Design

- **Mobile First**: Optimized for mobile devices
- **Grid Layout**: Responsive article grid
- **Touch Friendly**: Large touch targets
- **Readable Text**: Appropriate font sizes for all screens

## 🔍 Search & Filtering

### Search Capabilities
- **Full-Text Search**: Search in title, summary, and content
- **Topic Filtering**: Filter by predefined topics
- **Sort Options**: Multiple sorting methods
- **Real-time Results**: Instant search feedback

### Filter Options
- **By Topic**: Pet Care Tips, Veterinary Medicine, etc.
- **By Date**: Latest, oldest
- **By Popularity**: Most viewed
- **By Title**: Alphabetical order

## 💬 Comment System

### Features
- **User Authentication**: Login required to comment
- **Role Display**: Shows user type (Pet Owner, Doctor, etc.)
- **Moderation**: Authors and admins can delete comments
- **Timestamps**: Shows when comments were posted
- **Threaded Display**: Organized comment layout

## 📝 Inquiry System

### Features
- **Customer Support**: Users can submit inquiries about pet care
- **Status Tracking**: Inquiries show Pending, Replied, or Closed status
- **Staff Management**: Admin and staff can update inquiry statuses
- **User Interface**: Clean form for submitting new inquiries
- **Real-time Updates**: Status changes reflect immediately

### Inquiry Management
- **User Submission**: Logged-in users can submit inquiries
- **Staff Response**: Admin and staff can mark inquiries as replied
- **Status Control**: Staff can close completed inquiries
- **Audit Trail**: Full history of inquiry status changes

### Moderation
- **Author Control**: Article authors can delete comments
- **Admin Control**: Admins can delete any comment
- **Spam Protection**: Basic comment validation
- **Content Filtering**: HTML sanitization

## 📊 Analytics & Statistics

### Tracking
- **View Counts**: Monitor article popularity
- **Comment Counts**: Track engagement
- **Author Statistics**: Performance metrics
- **Topic Performance**: Popular content categories

### Insights
- **Popular Articles**: Most viewed content
- **Engagement Metrics**: Comments and interactions
- **Author Performance**: Content creation statistics
- **Topic Trends**: Popular veterinary topics

## 🚀 Performance Features

### Optimization
- **Database Indexing**: Optimized queries
- **Lazy Loading**: Efficient content loading
- **Caching**: Reduced database calls
- **Image Optimization**: Responsive images

### Scalability
- **Pagination**: Handle large numbers of articles
- **Search Optimization**: Efficient search algorithms
- **Database Views**: Pre-computed statistics
- **Modular Design**: Easy to extend

## 🔧 Customization

### Topics
- **Predefined Topics**: Standard veterinary categories
- **Easy Addition**: Simple to add new topics
- **Consistent Categorization**: Maintains organization

### Styling
- **Tailwind CSS**: Modern utility-first framework
- **Custom Colors**: PetVet brand colors
- **Responsive Components**: Mobile-optimized elements
- **Theme Support**: Easy to modify appearance

## 📈 Future Enhancements

### Planned Features
- **Article Scheduling**: Publish at specific times
- **Image Management**: Better image handling
- **SEO Optimization**: Meta tags and descriptions
- **Social Sharing**: Share articles on social media
- **Newsletter Integration**: Email article updates
- **Analytics Dashboard**: Detailed performance metrics

### Technical Improvements
- **API Endpoints**: RESTful API for mobile apps
- **Advanced Search**: Elasticsearch integration
- **Content Versioning**: Track article changes
- **Multi-language Support**: Internationalization
- **Advanced Moderation**: Automated content filtering

## 🐛 Troubleshooting

### Common Issues
1. **Database Connection**: Verify `../config.php` settings
2. **File Permissions**: Check folder access rights
3. **TinyMCE Loading**: Ensure internet connection for CDN
4. **Session Issues**: Verify session configuration

### Debug Mode
- Enable error reporting in PHP
- Check browser console for JavaScript errors
- Verify database table creation
- Test user authentication

## 📞 Support

For technical support or feature requests:
- Check the main PetVet documentation
- Review database setup instructions
- Verify file permissions and paths
- Test with sample data first

## 📄 License

This articles system is part of the PetVet veterinary clinic management system.
