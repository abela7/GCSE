# GCSE Tracker: Purpose and Structure

## Purpose of the Website

The GCSE Tracker is a comprehensive web application designed to help students prepare for their GCSE exams by tracking their study progress, organizing resources, and managing their exam preparation effectively. The primary purpose is to provide a centralized platform where students can:

1. **Track subject progress**: Monitor advancement through the GCSE curriculum for English and Mathematics
2. **Manage study time**: Record and analyze study sessions to ensure balanced preparation
3. **Organize tasks**: Create and track completion of study-related tasks and assignments
4. **Monitor exam dates**: Keep track of upcoming exams with countdown timers
5. **Collect resources**: Store and organize study materials and resources by subject
6. **Visualize progress**: See visual representations of progress to identify strengths and areas needing improvement

## Database Structure

The database is structured around two main subjects: English and Mathematics, with a flexible design that could be extended to other subjects.

### Subject Organization

#### English Structure:
- **Topics**: 6 main topics including Foundational Grammar, Reading Comprehension, Creative Writing, Non-Fiction Analysis, Transactional Writing, and Exam Preparation
- **Subtopics**: 90 detailed subtopics spread across these main topics
- **Progress tracking**: Individual progress records for each subtopic (status, confidence level, last studied date, notes)

#### Mathematics Structure:
- **Topics**: 6 main topics including Number, Algebra, Ratio/Proportion/Rates of Change, Geometry and Measure, Probability, and Statistics
- **Subtopics**: 107 detailed subtopics covering the mathematics curriculum
- **Progress tracking**: Individual progress records for each subtopic (status, confidence level, last studied date, notes)

### Key Features and Tables

1. **Subjects**: Core subjects (English, Mathematics) with color coding for visual identification
2. **Topics and Subtopics**: Hierarchical organization of curriculum content
3. **Progress Tracking**: Recording completion status and confidence levels for each subtopic
4. **Exams**: Tracking upcoming exams with details (date, duration, location, exam board)
5. **Tasks**: Managing study tasks with priorities, due dates, and completion status
6. **Study Sessions**: Recording study time with duration and subject focus
7. **Resources**: Organizing study materials by type (books, websites, videos, documents)
8. **Goals**: Setting and tracking study goals with target dates and progress

## Main Goals of the Application

The main goals of the GCSE Tracker are to:

1. **Provide structure to exam preparation**: Breaking down the curriculum into manageable chunks
2. **Increase student accountability**: Tracking progress and study time to encourage consistent effort
3. **Identify knowledge gaps**: Highlighting areas with low confidence or incomplete study
4. **Optimize study time**: Focusing attention on areas that need the most improvement
5. **Reduce exam anxiety**: Creating a sense of control and preparedness through organized tracking
6. **Improve time management**: Planning study sessions and tracking task completion
7. **Centralize resources**: Keeping all study materials organized and accessible

## Technical Implementation

The application is built using:
- **Frontend**: HTML, CSS (Bootstrap 5), JavaScript
- **Backend**: PHP
- **Database**: MySQL
- **Architecture**: Simple MVC-like structure with separate files for pages, includes, and configuration

The interface is designed to be responsive, intuitive, and visually appealing, with color-coding and progress bars to provide immediate visual feedback on progress.

## User Journey

1. Students start at the dashboard for an overview of their progress
2. They can navigate to specific subjects to see detailed topic breakdowns
3. They record study sessions after completing them
4. They can mark subtopics as "in progress" or "completed" and rate their confidence
5. They add and manage tasks related to their studies
6. They track upcoming exams and prepare accordingly
7. They organize their study resources for easy access

This comprehensive approach helps students take control of their GCSE preparation, ensuring they cover all necessary material and feel confident going into their exams.

# GCSE Study Tracker Web Application Development Specification

## Project Overview

This web application will help track, plan, and manage GCSE exam preparation for Mathematics and English subjects. The core purpose is to provide a comprehensive study planning tool with countdown timers, topic-level tracking, and resource management capabilities.

## Key Exam Dates to Feature
- Mathematics Paper 1: May 15, 2025 (Non-calculator exam)
- English Paper 1: May 23, 2025 (Focus on fiction & creative writing)
- Mathematics Paper 2: June 4, 2025 (Calculator exam)
- Mathematics Paper 3: June 11, 2025 (Calculator exam)
- English Paper 2: June 6, 2025 (Focus on non-fiction & transactional writing)

## Core Features

### 1. Dashboard/Home Page
- **Countdown Timers**: Display prominent countdowns to each exam date
- **Study Calendar**: Visual calendar showing the day-by-day study plan from March 27, 2025 until all exams
- **Progress Overview**: Visual indicators showing progress in both subjects and overall preparation
- **Quick Navigation**: Easy access to Mathematics and English sections
- **Today's Study Plan**: Display what topics should be studied today based on the predefined schedule

### 2. Mathematics Section

#### 2.1 Topic Organization Structure
Organize mathematics content into a three-tier hierarchy:
1. **Sections** (6 main sections)
   - Number
   - Algebra
   - Functions & Graphs
   - Ratio, Proportion & Rates
   - Geometry & Measures
   - Trigonometry

2. **Subsections** for each main section
   Example for Number section:
   - Place Value & Operations
   - Factors, Multiples & Primes
   - Accuracy & Estimation
   - Advanced Number Concepts

3. **Topics** within each subsection
   Example for Place Value & Operations:
   - Addition (Integers, Decimals, Fractions)
   - Subtraction (Integers, Decimals, Fractions)
   - Multiplication (Integers, Decimals, Fractions)
   - Division (Integers, Decimals, Fractions)
   - Order of Operations (BIDMAS/BODMAS)

#### 2.2 Topic Page Features
Each individual topic should have its own dedicated page with:
- **Time Tracker**: Log and display total time spent studying this topic
- **Confidence Rating**: Allow rating confidence level from 1-5 stars
- **Notes Section**: Text editor for storing personal notes on the topic
- **Questions Bank**: Area to add and answer practice questions
- **Resource Links**:
  - YouTube video links section
  - PDF/image upload capability
  - Web links to helpful resources
- **Completion Status**: Mark topics as "Not Started," "In Progress," or "Mastered"
- **Related Topics**: Links to related or prerequisite topics

### 3. English Section

Follow a similar hierarchical structure for English:

#### 3.1 Topic Organization Structure
1. **Sections**
   - Foundational Grammar
   - Reading Comprehension
   - Creative Writing
   - Non-Fiction Analysis
   - Transactional Writing

2. **Subsections** for each main section
   Example for Foundational Grammar:
   - Parts of Speech
   - Sentence Structure
   - Punctuation
   - Tenses

3. **Topics** within each subsection
   Example for Parts of Speech:
   - Nouns & Pronouns
   - Verbs & Adverbs
   - Adjectives
   - Articles & Determiners

#### 3.2 Topic Page Features
Same as Mathematics section, but tailored for English-specific needs.

### 4. Daily Planning & Tracking

#### 4.1 Daily Study Planner
- Display the predefined daily study plan based on the schedule provided
- Differentiate between work days (Friday-Sunday) and non-work days (Monday-Thursday)
- Show morning, afternoon, and evening session details
- Allow marking tasks as complete

#### 4.2 Daily Language Practice Tracker
- Track the daily 15-20 minute language practice sessions
- Include:
  - Spelling quiz tracking (record scores)
  - Vocabulary word list (add new words learned)
  - Idiom/Expression collection (save and review learned idioms)

#### 4.3 Time Management
- Allow logging actual time spent on each topic/task
- Compare planned vs. actual time spent
- Highlight areas receiving insufficient attention

### 5. Progress Analytics

#### 5.1 Topic Progress Visualization
- Heat map showing confidence levels across all topics
- Bar charts showing time spent per topic/section
- Completion percentage by section and overall

#### 5.2 Trend Analysis
- Weekly progress charts
- Time distribution analytics
- Confidence level changes over time

#### 5.3 Weak Areas Identification
- Automatically highlight topics with low confidence ratings
- Suggest additional focus areas based on time spent and confidence

### 6. User Experience Requirements

#### 6.1 Navigation
- Clean, intuitive navigation between Mathematics and English sections
- Breadcrumb navigation showing current location in topic hierarchy
- Quick return to dashboard from any page

#### 6.2 Responsive Design
- Fully responsive layout working on desktop, tablet, and mobile devices
- Touch-friendly interface for mobile users

#### 6.3 Data Persistence
- All user data should be saved automatically
- Implement local storage at minimum, database storage preferred

## Technical Requirements

### 1. Frontend
- Modern, clean UI with intuitive navigation
- Responsive design (mobile-friendly)
- Interactive elements for tracking and progress visualization
- Chart libraries for data visualization (e.g., Chart.js, D3.js)

### 2. Backend
- User authentication system (optional but recommended)
- Data storage for user progress, notes, and resources
- API endpoints for saving and retrieving study data

### 3. Data Structure
Implement the following data models:

#### 3.1 Subject
```
{
  id: String,
  name: String (Mathematics/English),
  sections: [Section],
  examDates: [Date]
}
```

#### 3.2 Section
```
{
  id: String,
  name: String,
  subjectId: String,
  subsections: [Subsection],
  completionStatus: Number (percentage)
}
```

#### 3.3 Subsection
```
{
  id: String,
  name: String,
  sectionId: String,
  topics: [Topic],
  completionStatus: Number (percentage)
}
```

#### 3.4 Topic
```
{
  id: String,
  name: String,
  subsectionId: String,
  description: String,
  timeSpent: Number (minutes),
  confidenceLevel: Number (1-5),
  notes: String,
  questions: [Question],
  resources: [Resource],
  completionStatus: String (Not Started/In Progress/Mastered),
  lastStudied: Date
}
```

#### 3.5 Resource
```
{
  id: String,
  topicId: String,
  type: String (YouTube/PDF/Image/Link),
  url: String,
  title: String,
  notes: String
}
```

#### 3.6 DailyPlan
```
{
  date: Date,
  isWorkDay: Boolean,
  mathsTopic: String,
  englishTopic: String,
  languagePracticeCompleted: Boolean,
  completedTasks: [String],
  notes: String
}
```

## Implementation Timeline (Suggested)

### Phase 1: Core Structure & Dashboard
- Setup project architecture
- Implement the dashboard with countdown timers
- Create basic navigation between Mathematics and English sections
- Implement the subject, section, subsection, and topic hierarchy

### Phase 2: Topic Pages & Tracking
- Develop individual topic pages with all required features
- Implement time tracking functionality
- Create confidence rating system
- Build notes and resources management

### Phase 3: Daily Planning & Progress Analytics
- Implement the daily study planner based on the predefined schedule
- Develop the progress visualization components
- Create analytics dashboards
- Implement weak areas identification

### Phase 4: Polish & Testing
- Ensure responsive design works on all devices
- Test data persistence and recovery
- Optimize performance
- Add any missing features or refinements

## Special Considerations

### Data Seeding
- Pre-populate the application with the complete Mathematics and English curriculum structure
- Include the day-by-day study plan from March 27 to all exam dates

### Offline Functionality
- Consider implementing Progressive Web App features for offline usage
- Ensure data syncs when connection is restored

### Notifications
- Optional: Add reminder notifications for daily study sessions
- Optional: Exam day countdown notifications

## Conclusion

This web application should serve as a comprehensive study companion that helps track progress, manage study time efficiently, and optimize preparation for GCSE Mathematics and English exams. The hierarchical organization of topics combined with detailed tracking features will provide actionable insights to improve study effectiveness.