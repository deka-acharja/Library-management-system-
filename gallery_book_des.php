<?php
include('includes/db.php');
include('includes/header.php');

// Get the genre name from the URL
$genre = isset($_GET['genre']) ? $_GET['genre'] : 'Drama';

// Get current page from URL, default to 1
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$books_per_page = 6;
$offset = ($page - 1) * $books_per_page;

// Array of detailed book descriptions
$descriptions = [
    'Drama' => [
        'description' => 'There are many types of drama like comedy, tragedy, fantasy, musical drama, and farce.
            COMEDY - Comedies are lighter in tone than ordinary writings and provide a happy conclusion. A comedy makes us laugh when well-composed with humorous elements.
            TRAGEDY - Tragic dramas are one of the oldest forms of drama. They expose human suffering and pain, often focusing on disaster, downfall, and emotional betrayals.
            FANTASY - A fictional work where characters display supernatural skills, appealing to children with fairies, angels, and superheroes.
            MUSICAL DRAMA - Music, melody, and dance play a significant role in musical drama.
            FARCE - A nonsensical genre often overacted with slapstick humor, improbable situations, and extravagant exaggeration.',
        'image' => 'images/gallery1.jpg'
    ],
    'Adventure Fiction' => [
        'description' => 'An adventure book description should capture the essence of a thrilling journey, highlighting the protagonist\'s quest, the dangers faced, and the potential for personal growth or transformation.
            Hook the Reader:
            Start with a captivating sentence that immediately grabs attention and hints at the adventure ahead.
            Introduce the Protagonist:
            Briefly introduce the main character and their motivation for embarking on the journey.
            Describe the Quest:
            Clearly outline the goal or mission that the protagonist is pursuing.',
        'image' => 'images/gallery2.jpg'
    ],
    'Autobiography' => [
        'description' => 'An autobiography is a book written by a person about their own life, offering a first-person, subjective account of their experiences, thoughts, and feelings.
            Definition:
            An autobiography is a narrative of a person\'s life, written by that person.
            Perspective:
            It is a first-person account, meaning the author is also the main character and narrator.',
        'image' => 'images/gallery3.jpg'
    ],
    'Biography' => [
        'description' => 'A biography is a detailed description of a person\'s life, including not just basic facts but a portrayal of the person\'s experiences and personality.
            Unlike a profile or CV, a biography presents a subject\'s life story, highlighting various aspects of their life and intimate details of their experience.',
        'image' => 'images/gallery4.jpg'
    ],
    'Classics' => [
        'description' => 'Classic literature is recognized for its enduring appeal and literary merit, often exploring timeless themes and well-developed characters.
            Key Characteristics:
            - Timelessness and enduring appeal
            - Universal themes such as love, loss, and morality
            - Memorable characters and intricate plots
            - Literary merit and cultural significance',
        'image' => 'images/gallery5.jpg'
    ],
    'Contemporary Literature' => [
        'description' => 'Contemporary literature generally covers works created from 1940 to the present, often reflecting modern social and political issues.
            Key Characteristics:
            - Time Period: Post-World War II
            - Themes: Identity, race, gender, sexuality, and mental health
            - Style: Realistic characters and modern writing styles
            - Genres: Literary fiction, popular fiction, genre fiction',
        'image' => 'images/gallery6.jpg'
    ],
    'Fairy Tale' => [
        'description' => 'A fairy tale is a genre of folklore, typically a short story featuring magic, mythical creatures, and often a happy ending, though originally intended for adults, they are now commonly associated with children literature. 
        Here is a more detailed breakdown:
        Folklore Genre:
        Fairy tales belong to the broader category of folklore, which encompasses oral traditions and stories passed down through generations. 
        Magical and Mythical Elements:
        They often feature magic, enchantments, and mythical or fanciful beings like fairies, witches, elves, and talking animals. 
        Short Stories:
        While some fairy tales have been expanded into longer works, they are generally characterized by their brevity and simplicity. 
        Happy Endings:
        Many fairy tales conclude with a "happily ever after" scenario, where the hero or heroine overcomes adversity and achieves their goals. 
        Oral Tradition:
        Originally, fairy tales were passed down orally, and only later were they written down by individual authors. 
        Not Always for Children:
        While often associated with children literature, fairy tales were initially intended for adult audiences and could contain themes and content that would be considered inappropriate for children today. 
        Examples:
        Classic examples include "Cinderella," "Little Red Riding Hood," "Snow White," and "The Three Little Pigs',
        'image' => 'images/gallery7.jpg'
    ],
    'Fantasy' => [
        'description' => 'Fantasy, as a book category, is a genre of speculative fiction characterized by magical elements, often set in fictional universes, and sometimes drawing inspiration from mythology and folklore. 
        Here is a more detailed breakdown:
        Definition:
        Fantasy is a genre of fiction that features elements of the supernatural, magic, and often takes place in a world that is not our own. 
        Key Characteristics:
        Fictional Worlds: Fantasy stories often take place in invented worlds with their own rules, cultures, and histories. 
        Magic and the Supernatural: Magic, mythical creatures, and other supernatural phenomena are common themes in fantasy. 
        Mythological and Folkloric Influences: Many fantasy stories draw inspiration from myths, legends, and folklore from different cultures. 
        Subgenres:
        Fantasy encompasses a wide range of subgenres, including:
        High Fantasy: Epic stories set in a secondary world with complex magic systems and often featuring quests and battles against great evil. 
        Low Fantasy: Stories set in a world that is similar to our own, where magic is more subtle or less pervasive. 
        Dark Fantasy: Fantasy stories that incorporate elements of horror and explore darker themes. 
        Urban Fantasy: Fantasy stories set in modern-day cities, where magical elements exist alongside everyday life. 
        Sword and Sorcery: Stories focused on heroic warriors and their adventures, often featuring battles and magic. 
        Science Fantasy: A blend of fantasy and science fiction, often featuring advanced technology and space travel alongside magic and mythical creatures. 
        Examples:
        The Lord of the Rings by J.R.R. Tolkien (High Fantasy) 
        The Chronicles of Narnia by C.S. Lewis (High Fantasy)
        A Game of Thrones by George R.R. Martin (High Fantasy)
        The Witcher by Andrzej Sapkowski (Dark Fantasy)
        Mistborn by Brandon Sanderson (High Fantasy)
        The Dresden Files by Jim Butcher (Urban Fantasy)
        The Dark Is Rising by Susan Cooper (Historical Fantasy',
        'image' => 'images/gallery8.jpg'
    ],
    'Graphic Novels' => [
        'description' => '"Graphic Novel" is a format, not a genre. Graphic novels can be fiction, non-fiction, history, fantasy, or anything in-between.
        Graphic novels are similar to comic books because they use sequential art to tell a story. Unlike comic books, graphic novels are generally stand-alone stories with more complex plots. Collections of short stories that have been previously published as individual comic books are also considered graphic novels.
        Major Types of Graphic Novels
        Like traditional novels there are endless ways to categorize different graphic novels. There are as many genres and sub-genres as in traditional fiction and non-fiction. The following are only a few of the most predominant categories.
        Manga: The Japanese word for "comic" but in the US is used to descript Japanese style comics. Manga is read from top to bottom and right to left as this is the traditional Japanese reading pattern. Though, technically Manga refers to Japanese comics, many think Manga refers to a style rather than the country of origin.
        Titles: Death Note, Full Metal Alchemist
        Superhero Story: Superhero graphic novels have taken the most popular form of comics and turned what were once brief episodic adventures into epic sagas. Superhero comics are dominated by a few mainstream publishers Marvel, DC, and Darkhorse.
        Titles: Batman: Dark Knight Returns, League of Extraordinary Gentlemen, Astrocity.
        Personal Narratives ("Perzines"): are autobiographical stories written from the author personal experiences, opinions, and observations.
        Titles: Fun Home, Blankets, Lucky, The Quitter.
        Non-fiction: are similar to perzine in that they are written from the author personal experience, but the author is generally using their own experience to touch upon a greater social issue.
        Titles: Pedro and Me, Maus, Persepolis.',
        'image' => 'images/gallery9.jpg'
    ],
    'Historical Fiction' => [
        'description' => 'Historical fiction is a literary genre where stories are set in the past, often incorporating real historical events, figures, and settings, while allowing for fictionalized characters and plotlines. 
        Here is a more detailed description:
        Setting:
        The core characteristic of historical fiction is its setting in a specific period of the past. 
        Fictionalized Elements:
        While drawing inspiration from history, historical fiction allows authors to create fictional characters, storylines, and dialogues within the historical context. 
        Historical Accuracy:
        Authors often strive for accuracy in portraying the social norms, customs, and details of the time period, contributing to the authenticity of the story. 
        Themes:
        Historical fiction can explore universal themes like love, loss, identity, power, and social change, often using the past as a backdrop to examine these themes. 
        Examples
        Some examples of historical fiction include "The Nightingale" by Kristin Hannah, "The Help" by Kathryn Stockett, and "The Book Thief" by Markus Zusak ',
        'image' => 'images/gallery10.jpg'
    ],
    'History' => [
        'description' => 'The category of "book history" encompasses the study and exploration of the evolution, production, and cultural significance of books, including their authors, publishers, formats, and impact on society. 
        Here is a more detailed look:
        What it encompasses:
        Evolution of Book Formats:
        Tracing the development of books from ancient scrolls and codices to modern formats like e-books. 
        History of Printing and Publishing:
        Examining the invention of printing, the rise of publishing houses, and the impact of mass production on book accessibility. 
        Authors and Their Works:
        Investigating the lives, careers, and literary legacies of authors, as well as the historical context in which their works were written. 
        The Role of Books in Society:
        Analyzing the cultural, social, and political impact of books, including their role in education, entertainment, and the transmission of knowledge. 
        Book Collecting and Rare Books:
        Exploring the world of bibliophiles, rare book collecting, and the preservation of historical books. 
        Historical Fiction:
        Examining novels that are set in the past and attempt to convey the spirit, manners, and social conditions of a past age with realistic detail. 
        Historical Books in the Bible:
        The Old Testament contains several books that touch on historical events, such as Genesis, Exodus, Leviticus, Numbers, Deuteronomy, Joshua, Judges, Ruth, 1-2 Samuel, 1-2 Kings, 1-2 Chronicles, Ezra-Nehemiah, and Esther. 
        Why it is important:
        Understanding Culture and Society:
        Books are a window into the past, allowing us to understand the values, beliefs, and experiences of different cultures and societies. 
        Preserving Knowledge:
        Books are a vital tool for preserving knowledge and transmitting it to future generations. 
        Appreciating Literary History:
        Studying book history helps us appreciate the rich and diverse history of literature and the authors who shaped it. 
        Critical Thinking:
        Analyzing books and their historical contexts can foster critical thinking skills and a deeper understanding of the world. ',
        'image' => 'images/gallery11.jpg'
    ],
    'Horror' => [
        'description' => 'The horror genre in literature aims to evoke strong feelings of fear, dread, and revulsion in the reader, often through supernatural elements, psychological exploration, or disturbing imagery, with roots in ancient tales and evolving into various subgenres. 
                        Here is a more detailed look at the horror genre:
                        Core Characteristics:
                        Eliciting Fear and Dread:
                        The primary goal of horror is to scare and unsettle the reader, creating a sense of unease and terror. 
                        Exploring Dark Themes:
                        Horror often delves into dark and unsettling themes, such as death, evil, the supernatural, and the unknown. 
                        Supernatural Elements:
                        Many horror stories feature supernatural elements like ghosts, vampires, werewolves, and other monsters. 
                        Psychological Horror:
                        Some horror stories focus on psychological manipulation, paranoia, and the exploration of the human psyche. 
                        Disturbing Imagery:
                        Horror often employs vivid and disturbing imagery to create a sense of fear and revulsion. 
                        Atmosphere and Setting:
                        Horror stories often take place in dark and foreboding settings, such as old houses, dark forests, or isolated locations, which contribute to the overall atmosphere of dread. 
                        Subgenres of Horror:
                        Supernatural Horror: Focuses on the supernatural, including ghosts, demons, and other otherworldly entities. 
                        Psychological Horror: Explores the human mind and its capacity for fear, anxiety, and madness. 
                        Gothic Horror: Characterized by dark and atmospheric settings, often featuring castles, ruins, and decaying landscapes. 
                        Cosmic Horror: Deals with the vastness and indifference of the universe, often featuring alien or otherworldly beings. 
                        Monster Horror: Focuses on monsters like werewolves, vampires, zombies, etc 
                        Folk Horror: Takes a folk tale or traditional lesson and flips it into a horror story 
                        Historical Context:
                        Ancient Roots:
                        Horror stories can be traced back to ancient Greek and Roman times, with tales of monsters, ghosts, and the afterlife. 
                        Gothic Era:
                        The 18th century saw the rise of Gothic horror, with authors like Edgar Allan Poe and Mary Shelley exploring dark and macabre themes. 
                        Modern Horror:
                        Modern horror literature continues to evolve, with new subgenres and themes emerging. ',
        'image' => 'images/gallery12.jpg'
    ],
    'Literary Fiction' => [
        'description' => 'The category of Literary Fiction is quite fluid and for the last few decades has easily overlapped with any number of genres. Even though its definition is a broad target, Literary Fiction definitely has characteristics of its own.
        Whereas genre fiction from Romance to Dystopian Horror is plot-driven, Literary Fiction is character-driven. Any action in the story impacts the main character or characters, and understanding this impact is the whole point of telling the story. 
        The overall tone of the book is introspective. Literary Fiction, then, is always a study of the human condition and often an exploration of difficult social or political issues that control our lives. For this reason, it’s generally considered more “serious” than genre fiction.
        Another way to recognize Literary Fiction is by its story structure. Unlike, say, Thrillers or Science Fiction, Literary Fiction doesn’t follow a formula. A story arc may or may not be present, which also means that a satisfying ending is no guarantee. 
        The line between hero and villain is often blurry, as is what they are trying to accomplish. And without a tidy plot to spell out every character’s motive, intangible details — metaphor, symbolism, or imagery, for example — play a larger role in telling the story.',
        'image' => 'images/gallery13.jpg'
    ],
    'Mystery' => [
        'description' => 'The mystery genre in books revolves around a central enigma, often a crime or disappearance, that a protagonist or reader must unravel through clues and deduction, leading to a satisfying resolution. 
            Here is a more detailed look at the mystery genre:
            Core Elements:
            A Crime or Mystery: The genre typically features a crime, a disappearance, or some other event that is initially mysterious and unexplained. 
            Protagonist/Detective: A character, often a detective or investigator, is tasked with solving the mystery. 
            Clues and Red Herrings: The story unfolds with clues, some leading to the truth and others designed to mislead the reader (red herrings). 
            Suspects: A group of potential suspects, each with motives and opportunities, creates suspense and encourages the reader to analyze the situation. 
            Resolution: The mystery is eventually solved, with the truth revealed and the case closed. 
            Subgenres:
            Cozy Mysteries: Often set in small towns or villages, with a focus on the crime itself rather than graphic violence. 
            Hard-boiled Detective: Features tough, cynical detectives in gritty urban settings. 
            Psychological Thrillers: Focus on the characters minds and the psychological aspects of the crime, rather than the mechanics of solving it. 
            Historical Mysteries: Set in the past, often with historical figures or events as part of the plot. 
            Characteristics:
            Suspense and Intrigue: The genre thrives on creating a sense of suspense and intrigue, keeping the reader guessing until the end. 
            Deduction and Logic: Readers are encouraged to use their own deductive skills to solve the mystery alongside the protagonist. 
            Clues and Red Herrings: Authors use clues and red herrings to guide the readers thinking and create a sense of mystery. 
            Closed Circle of Suspects: Often, the mystery takes place within a closed circle of suspects, making it easier for the reader to analyze the situation. ',
        'image' => 'images/gallery14.jpg'
    ],
    'Non-Fiction' => [
        'description' => 'Non-fiction books focus on presenting factual information and real-world events, encompassing genres like biography, history, science, and journalism, unlike fiction which relies on imagination. 
        Here is a more detailed look at the category of non-fiction books:
        Definition:
        Non-fiction encompasses books that aim to convey information about the real world, focusing on facts, historical events, and scientific data, rather than being based on imagination. 
        Key Characteristics:
        Factual Basis: Non-fiction relies on real events, people, and places, aiming for accuracy and objectivity. 
        Informative Nature: The primary purpose of non-fiction is to educate, inform, or provide insights into a specific topic. 
        Variety of Genres: Non-fiction includes a wide range of genres, including biography, history, science, travel, self-help, true crime, and journalism, among others. 
        Examples of Non-Fiction Genres:
        Biography: Accounts of a person life written by someone else. 
        Autobiography/Memoir: A narrative of a person life written by themselves, often focusing on specific periods or experiences. 
        History: Accounts of past events and civilizations. 
        Science: Explanations and discussions of scientific concepts and discoveries. 
        Travel Writing: Accounts of journeys and exploration of different places. 
        Self-Help: Books offering advice and guidance on personal development and problem-solving. 
        True Crime: Accounts of real-life crimes and investigations. 
        Journalism: Reporting on current events and issues. ',
        'image' => 'images/gallery15.jpg'
    ],
    'Poetry' => [
        'description' => 'Poetry is a broad literary category that covers a variety of writing, including bawdy limericks, unforgettable song lyrics, and even the sentimental couplets inside greeting cards. Some kinds of poetry have few rules, while others have a rigid structure. That can make poetry feel hard to define, but the variety is also what makes it enjoyable. Through poetry, writers can express themselves in ways they can’t always through prose.
        There are more than 150 types of poetry from cultures all over the world. Here, we’ll look at some of the key types of poetry to know, explain how they’re structured, and give plenty of examples.
        Key poetry terms
        To better understand the differences between types of poetry, it’s important to know the following poetry terms:
        Rhyme: Repeated sounds in two or more words. Usually, rhyming sounds are at the ends of words, but this is not always the case. A poem’s rhyme scheme is the pattern its rhymes follow.
        Meter: A poem’s meter is its rhythmic structure. The number of syllables in a line and their emphasis compose a poem’s meter.
        Form: The overall structure of a poem is known as its form. A poem’s form can determine its meter and rhyme scheme.
        Stanza: A stanza is a section of a poem. Think of it like a verse in a song or a paragraph in an essay. Stanzas compose a poem’s form. In a poem, the stanzas can all fit the same meter, or they can vary.
        Not all poems have a rhyme scheme, a form, or a meter. A poem might have one or two of these, or it could have all three. Many types of poetry are defined by a specific form, rhyme scheme, or meter. When you set out to write a poem, think about which form—if any—best suits your subject matter. Generally, poetic forms don’t include rules for using punctuation, 
        such as periods and quotation marks, so you have some wiggle room with these.
        With poetry, finding ways to fit your words into a form can be just as enjoyable as breaking the rules! Check out these quotes from famous poets about reading and writing poetry.',
        'image' => 'images/gallery16.jpg'
    ],
    'Romance Novels' => [
        'description' => 'A novel is a long, fictional narrative, typically characterized by a complex plot, well-developed characters, and a literary prose style, often used for entertainment or to explore themes and ideas. 
        Here is a more detailed breakdown:
        Definition:
        A novel is a genre of fiction, meaning it tells a story that is not necessarily based on real events or people, but rather is a product of the author imagination. 
        Length:
        Novels are generally longer than short stories or novellas, often exceeding 40,000 words. 
        Structure:
        Novels typically have a structured narrative with a beginning, middle, and end, featuring a plot, characters, setting, and theme. 
        Purpose:
        Novels can serve various purposes, including entertainment, exploration of human nature, social commentary, and the conveyance of ideas. 
        Genres:
        Novels can belong to various genres, such as historical fiction, science fiction, fantasy, romance, mystery, thriller, and many others. 
        Examples:
        Some famous examples of novels include "Pride and Prejudice" by Jane Austen, "To Kill a Mockingbird" by Harper Lee, and "1984" by George Orwell. ',
        'image' => 'images/gallery17.jpg'
    ],
    'Satire' => [
        'description' => 'Satire is a genre of the visual, literary, and performing arts, usually in the form of fiction and less frequently non-fiction, in which vices, follies, abuses, and shortcomings are held up to ridicule, often with the intent of exposing or shaming the perceived flaws of individuals, corporations, government, 
        or society itself into improvement.[1] Although satire is usually meant to be humorous, its greater purpose is often constructive social criticism, using wit to draw attention to both particular and wider issues in society.
        A prominent feature of satire is strong irony or sarcasm—"in satire, irony is militant", according to literary critic Northrop Frye—[2] but parody, burlesque, exaggeration,[3] juxtaposition, comparison, analogy, and double entendre are all frequently used in satirical speech and writing. 
        This "militant" irony or sarcasm often professes to approve of (or at least accept as natural) the very things the satirist wishes to question.
        Satire is found in many artistic forms of expression, including internet memes, literature, plays, commentary, music, film and television shows, and media such as lyrics.',
        'image' => 'images/gallery18.jpg'
    ],
     'Science Fiction' => [
        'description' => 'Satire is a genre of the visual, literary, and performing arts, usually in the form of fiction and less frequently non-fiction, in which vices, follies, abuses, and shortcomings are held up to ridicule, often with the intent of exposing or shaming the perceived flaws of individuals, corporations, government, 
        or society itself into improvement.[1] Although satire is usually meant to be humorous, its greater purpose is often constructive social criticism, using wit to draw attention to both particular and wider issues in society.
        A prominent feature of satire is strong irony or sarcasm—"in satire, irony is militant", according to literary critic Northrop Frye—[2] but parody, burlesque, exaggeration,[3] juxtaposition, comparison, analogy, and double entendre are all frequently used in satirical speech and writing. 
        This "militant" irony or sarcasm often professes to approve of (or at least accept as natural) the very things the satirist wishes to question.
        Satire is found in many artistic forms of expression, including internet memes, literature, plays, commentary, music, film and television shows, and media such as lyrics.',
        'image' => 'images/gallery19.jpg'
    ],
        'Self Help Book' => [
        'description' => 'Satire is a genre of the visual, literary, and performing arts, usually in the form of fiction and less frequently non-fiction, in which vices, follies, abuses, and shortcomings are held up to ridicule, often with the intent of exposing or shaming the perceived flaws of individuals, corporations, government, 
        or society itself into improvement.[1] Although satire is usually meant to be humorous, its greater purpose is often constructive social criticism, using wit to draw attention to both particular and wider issues in society.
        A prominent feature of satire is strong irony or sarcasm—"in satire, irony is militant", according to literary critic Northrop Frye—[2] but parody, burlesque, exaggeration,[3] juxtaposition, comparison, analogy, and double entendre are all frequently used in satirical speech and writing. 
        This "militant" irony or sarcasm often professes to approve of (or at least accept as natural) the very things the satirist wishes to question.
        Satire is found in many artistic forms of expression, including internet memes, literature, plays, commentary, music, film and television shows, and media such as lyrics.',
        'image' => 'images/gallery20.jpg'
    ],
        'Short Story' => [
        'description' => 'Satire is a genre of the visual, literary, and performing arts, usually in the form of fiction and less frequently non-fiction, in which vices, follies, abuses, and shortcomings are held up to ridicule, often with the intent of exposing or shaming the perceived flaws of individuals, corporations, government, 
        or society itself into improvement.[1] Although satire is usually meant to be humorous, its greater purpose is often constructive social criticism, using wit to draw attention to both particular and wider issues in society.
        A prominent feature of satire is strong irony or sarcasm—"in satire, irony is militant", according to literary critic Northrop Frye—[2] but parody, burlesque, exaggeration,[3] juxtaposition, comparison, analogy, and double entendre are all frequently used in satirical speech and writing. 
        This "militant" irony or sarcasm often professes to approve of (or at least accept as natural) the very things the satirist wishes to question.
        Satire is found in many artistic forms of expression, including internet memes, literature, plays, commentary, music, film and television shows, and media such as lyrics.',
        'image' => 'images/gallery21.jpg'
    ],
        'Thriller' => [
        'description' => 'Satire is a genre of the visual, literary, and performing arts, usually in the form of fiction and less frequently non-fiction, in which vices, follies, abuses, and shortcomings are held up to ridicule, often with the intent of exposing or shaming the perceived flaws of individuals, corporations, government, 
        or society itself into improvement.[1] Although satire is usually meant to be humorous, its greater purpose is often constructive social criticism, using wit to draw attention to both particular and wider issues in society.
        A prominent feature of satire is strong irony or sarcasm—"in satire, irony is militant", according to literary critic Northrop Frye—[2] but parody, burlesque, exaggeration,[3] juxtaposition, comparison, analogy, and double entendre are all frequently used in satirical speech and writing. 
        This "militant" irony or sarcasm often professes to approve of (or at least accept as natural) the very things the satirist wishes to question.
        Satire is found in many artistic forms of expression, including internet memes, literature, plays, commentary, music, film and television shows, and media such as lyrics.',
        'image' => 'images/gallery22.jpg'
    ],
        'Western Fiction' => [
        'description' => 'Satire is a genre of the visual, literary, and performing arts, usually in the form of fiction and less frequently non-fiction, in which vices, follies, abuses, and shortcomings are held up to ridicule, often with the intent of exposing or shaming the perceived flaws of individuals, corporations, government, 
        or society itself into improvement.[1] Although satire is usually meant to be humorous, its greater purpose is often constructive social criticism, using wit to draw attention to both particular and wider issues in society.
        A prominent feature of satire is strong irony or sarcasm—"in satire, irony is militant", according to literary critic Northrop Frye—[2] but parody, burlesque, exaggeration,[3] juxtaposition, comparison, analogy, and double entendre are all frequently used in satirical speech and writing. 
        This "militant" irony or sarcasm often professes to approve of (or at least accept as natural) the very things the satirist wishes to question.
        Satire is found in many artistic forms of expression, including internet memes, literature, plays, commentary, music, film and television shows, and media such as lyrics.',
        'image' => 'images/gallery23.jpg'
    ],
        'Young Adult' => [
        'description' => 'Satire is a genre of the visual, literary, and performing arts, usually in the form of fiction and less frequently non-fiction, in which vices, follies, abuses, and shortcomings are held up to ridicule, often with the intent of exposing or shaming the perceived flaws of individuals, corporations, government, 
        or society itself into improvement.[1] Although satire is usually meant to be humorous, its greater purpose is often constructive social criticism, using wit to draw attention to both particular and wider issues in society.
        A prominent feature of satire is strong irony or sarcasm—"in satire, irony is militant", according to literary critic Northrop Frye—[2] but parody, burlesque, exaggeration,[3] juxtaposition, comparison, analogy, and double entendre are all frequently used in satirical speech and writing. 
        This "militant" irony or sarcasm often professes to approve of (or at least accept as natural) the very things the satirist wishes to question.
        Satire is found in many artistic forms of expression, including internet memes, literature, plays, commentary, music, film and television shows, and media such as lyrics.',
        'image' => 'images/gallery24.jpg'
    ],
];

// Get the description and image for the selected genre
$genreData = $descriptions[$genre] ?? ['description' => "Description not available.", 'image' => 'images/default.jpg'];
$description = $genreData['description'];
$image = $genreData['image'];

// Count total books for pagination
$count_sql = "SELECT COUNT(*) as total FROM books WHERE genre = ?";
$count_stmt = $conn->prepare($count_sql);
if ($count_stmt === false) {
    die("SQL prepare failed: " . $conn->error);
}
$count_stmt->bind_param("s", $genre);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_books = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_books / $books_per_page);

// Query for books in this genre with pagination
$sql = "SELECT title, author, publication_details, isbn FROM books WHERE genre = ? LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("SQL prepare failed: " . $conn->error);
}

$stmt->bind_param("sii", $genre, $books_per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($genre); ?> | Literary Explorer</title>
    <link rel="stylesheet" href="app.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --main-green: #2e6a33;
            --dark-green: #1d4521;
            --light-green: #c9dccb;
            --accent-green: #4a9950;
            --ultra-light-green: #eef5ef;
            --text-dark: #263028;
            --text-medium: #45634a;
            --text-light: #6b8f70;
            --white: #ffffff;
            --error-red: #d64541;
            --success-green: #2ecc71;
            --warning-yellow: #f39c12;
            --border-radius: 12px;
            --border-radius-lg: 24px;
            --shadow-sm: 0 1px 3px rgba(46, 106, 51, 0.08);
            --shadow: 0 4px 6px rgba(46, 106, 51, 0.12);
            --shadow-lg: 0 10px 15px rgba(46, 106, 51, 0.15);
            --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--white);
            color: var(--text-dark);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            padding-top: 270px;
        }

        /* Modern Layout */
        .app-container {
            display: grid;
            grid-template-rows: auto 1fr auto;
            min-height: 100vh;
            margin-top: 0px;
        }

        /* Main Content */
        .main-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
            width: 100%;
        }

        /* Modern Breadcrumbs */
        .breadcrumbs {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
            font-size: 0.9rem;
            color: var(--text-light);
        }

        .breadcrumb-link {
            color: var(--text-medium);
            text-decoration: none;
            transition: var(--transition);
            display: flex;
            align-items: center;
        }

        .breadcrumb-link:hover {
            color: var(--main-green);
        }

        .breadcrumb-separator {
            margin: 0 0.5rem;
            color: var(--light-green);
        }

        .breadcrumb-current {
            color: var(--main-green);
            font-weight: 500;
        }

        /* Modern Hero Section */
        .genre-hero {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            align-items: center;
            margin-bottom: 4rem;
        }

        .genre-hero-image {
            border-radius: var(--border-radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            aspect-ratio: 1/1;
        }

        .genre-hero-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
        }

        .genre-hero-content {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .genre-tag {
            display: inline-block;
            background: var(--light-green);
            color: var(--main-green);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            width: fit-content;
        }

        .genre-title {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 3rem;
            font-weight: 700;
            line-height: 1.1;
            color: var(--text-dark);
            margin: 0;
        }

        .genre-description {
            color: var(--text-medium);
            font-size: 1.1rem;
            line-height: 1.7;
        }

        /* Genre Details */
        .genre-details {
            background: var(--ultra-light-green);
            border-radius: var(--border-radius-lg);
            padding: 3rem;
            margin-bottom: 4rem;
            position: relative;
            overflow: hidden;
        }

        .genre-details::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(to bottom, var(--main-green), var(--accent-green));
        }

        .section-title {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.75rem;
            font-weight: 600;
            color: var(--main-green);
            margin-bottom: 1.5rem;
            position: relative;
            display: inline-block;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 50%;
            height: 3px;
            background: linear-gradient(to right, var(--main-green), var(--accent-green));
            border-radius: 3px;
        }

        /* Books Grid */
        .books-section {
            margin-bottom: 4rem;
        }

        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
        }

        /* Modern Book Card */
        .book-card {
            background: var(--white);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            border: 1px solid var(--light-green);
            position: relative;
        }

        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .book-cover {
            aspect-ratio: 2/3;
            overflow: hidden;
            position: relative;
        }

        .book-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
        }

        .book-card:hover .book-cover img {
            transform: scale(1.05);
        }

        .book-info {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .book-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-dark);
            line-height: 1.4;
            display: -webkit-box;
            /* -webkit-line-clamp: 2; */
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .book-author {
            font-size: 0.9rem;
            color: var(--text-light);
        }

        .book-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .meta-item {
            background: var(--ultra-light-green);
            color: var(--text-medium);
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .book-actions {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--light-green);
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            text-decoration: none;
            transition: var(--transition);
            cursor: pointer;
            border: none;
            font-size: 0.95rem;
            gap: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--main-green) 0%, var(--accent-green) 100%);
            color: var(--white);
            box-shadow: var(--shadow-sm);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--dark-green) 0%, var(--main-green) 100%);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .btn-outline {
            background: transparent;
            color: var(--main-green);
            border: 1px solid var(--main-green);
        }

        .btn-outline:hover {
            background: var(--main-green);
            color: var(--white);
        }

        /* Empty State */
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 4rem 2rem;
            background: var(--ultra-light-green);
            border-radius: var(--border-radius);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }

        .empty-icon {
            font-size: 3rem;
            color: var(--accent-green);
            opacity: 0.7;
        }

        .empty-text {
            color: var(--text-light);
            font-size: 1.1rem;
            max-width: 500px;
            margin: 0 auto;
        }


        /* Responsive Design */
        @media (max-width: 1024px) {
            .genre-hero {
                grid-template-columns: 1fr;
            }

            .genre-hero-image {
                aspect-ratio: 16/9;
            }

            .genre-title {
                font-size: 2.5rem;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 1.5rem;
            }

            .genre-title {
                font-size: 2rem;
            }

            .books-grid {
                grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            }
        }

        @media (max-width: 480px) {
            .header-container {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem 0;
            }

            .genre-details {
                padding: 2rem 1.5rem;
            }

            .section-title {
                font-size: 1.5rem;
            }
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade {
            animation: fadeIn 0.6s ease-out;
        }

        .animate-slide-up {
            animation: slideUp 0.6s ease-out;
        }

        .delay-1 {
            animation-delay: 0.1s;
        }

        .delay-2 {
            animation-delay: 0.2s;
        }

        .delay-3 {
            animation-delay: 0.3s;
        }

        /* Loading state for images */
        .book-cover img {
            background: linear-gradient(135deg, var(--ultra-light-green) 0%, var(--light-green) 100%);
        }

        /* Modern scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--ultra-light-green);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--main-green);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--dark-green);
        }

        /* Additional styles for pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin: 40px 0;
            flex-wrap: wrap;
        }

        .pagination a,
        .pagination span {
            padding: 12px 16px;
            text-decoration: none;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            color: #333;
            font-weight: 500;
            transition: all 0.3s ease;
            min-width: 44px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .pagination a:hover {
            background-color: #f8f9fa;
            border-color: #007bff;
            color: #007bff;
            transform: translateY(-2px);
        }

        .pagination .current {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }

        .pagination .disabled {
            color: #ccc;
            cursor: not-allowed;
            background-color: #f8f9fa;
        }

        .pagination .disabled:hover {
            transform: none;
            background-color: #f8f9fa;
            border-color: #e0e0e0;
            color: #ccc;
        }

        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }

        .book-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: 1px solid #f0f0f0;
        }

        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }

        .book-info h3 {
            font-size: 1.4em;
            margin-bottom: 10px;
            color: #2c3e50;
            font-weight: 600;
        }

        .book-author {
            color: #7f8c8d;
            font-size: 1.1em;
            margin-bottom: 15px;
            font-style: italic;
        }

        .book-meta {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: 15px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
            font-size: 0.9em;
        }

        .meta-item i {
            color: #007bff;
            width: 16px;
        }

        .pagination-info {
            text-align: center;
            margin: 20px 0;
            color: #666;
            font-size: 0.95em;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-icon {
            font-size: 4em;
            color: #ddd;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 1.5em;
            margin-bottom: 10px;
            color: #333;
        }

        .empty-text {
            margin-bottom: 25px;
            font-size: 1.1em;
        }
    </style>
</head>

<body class="app-container">
    <main class="main-content">
        <!-- Modern Breadcrumbs -->
        <div class="breadcrumbs animate-fade">
            <a href="index.php" class="breadcrumb-link">Home</a>
            <span class="breadcrumb-separator">/</span>
            <a href="gallery.php" class="breadcrumb-link">Genres</a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current"><?php echo htmlspecialchars($genre); ?></span>
        </div>

        <!-- Modern Hero Section -->
        <section class="genre-hero">
            <div class="genre-hero-image animate-slide-up">
                <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($genre); ?>"
                    loading="lazy">
            </div>
            <div class="genre-hero-content animate-slide-up delay-1">
                <span class="genre-tag">Literary Genre</span>
                <h1 class="genre-title"><?php echo htmlspecialchars($genre); ?></h1>
                <p class="genre-description">Explore our curated collection of <?php echo htmlspecialchars($genre); ?>
                    titles that will transport you to different worlds and experiences.</p>
                <div class="btn-group">
                    <a href="gallery.php" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i> All Genres
                    </a>
                </div>
            </div>
        </section>

        <!-- Genre Details Section -->
        <section class="genre-details animate-slide-up delay-2">
            <h2 class="section-title">About <?php echo htmlspecialchars($genre); ?></h2>
            <div class="genre-description">
                <?php echo nl2br(htmlspecialchars($description)); ?>
            </div>
        </section>

        <!-- Books Section -->
        <section class="books-section">
            <h2 class="section-title animate-slide-up delay-1"><?php echo htmlspecialchars($genre); ?> Collection</h2>

            <?php if ($total_books > 0): ?>
                <!-- Pagination Info -->
                <div class="pagination-info">
                    Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $books_per_page, $total_books); ?> of
                    <?php echo $total_books; ?> books
                </div>

                <div class="books-grid">
                    <?php while ($book = $result->fetch_assoc()): ?>
                        <div class="book-card animate-slide-up delay-2">
                            <div class="book-info">
                                <h3 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h3>
                                <p class="book-author">by <?php echo htmlspecialchars($book['author'] ?: 'Unknown Author'); ?>
                                </p>
                                <div class="book-meta">
                                    <span class="meta-item">
                                        <i class="fas fa-barcode"></i>
                                        <strong>ISBN:</strong> <?php echo htmlspecialchars($book['isbn'] ?: 'Not Available'); ?>
                                    </span>
                                    <span class="meta-item">
                                        <i class="fas fa-calendar-alt"></i>
                                        <strong>Publication:</strong>
                                        <?php echo htmlspecialchars($book['publication_details'] ?: 'Not Available'); ?>
                                    </span>
                                    <span class="meta-item">
                                        <i class="fas fa-tag"></i>
                                        <strong>Genre:</strong> <?php echo htmlspecialchars($genre); ?>
                                    </span>
                                </div>
                                <div class="book-actions" style="margin-top: 20px;">
                                    <div class="login-prompt">
                                        <a href="login.php" class="btn btn-primary" style="width: 100%;">
                                            <i class="fas fa-sign-in-alt"></i> Login to View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <!-- Previous Button -->
                        <?php if ($page > 1): ?>
                            <a href="?genre=<?php echo urlencode($genre); ?>&page=<?php echo $page - 1; ?>" title="Previous Page">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php else: ?>
                            <span class="disabled">
                                <i class="fas fa-chevron-left"></i>
                            </span>
                        <?php endif; ?>

                        <!-- Page Numbers -->
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);

                        // Show first page if not visible
                        if ($start_page > 1): ?>
                            <a href="?genre=<?php echo urlencode($genre); ?>&page=1">1</a>
                            <?php if ($start_page > 2): ?>
                                <span class="disabled">...</span>
                            <?php endif; ?>
                        <?php endif; ?>

                        <!-- Current range of pages -->
                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?genre=<?php echo urlencode($genre); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <!-- Show last page if not visible -->
                        <?php if ($end_page < $total_pages): ?>
                            <?php if ($end_page < $total_pages - 1): ?>
                                <span class="disabled">...</span>
                            <?php endif; ?>
                            <a
                                href="?genre=<?php echo urlencode($genre); ?>&page=<?php echo $total_pages; ?>"><?php echo $total_pages; ?></a>
                        <?php endif; ?>

                        <!-- Next Button -->
                        <?php if ($page < $total_pages): ?>
                            <a href="?genre=<?php echo urlencode($genre); ?>&page=<?php echo $page + 1; ?>" title="Next Page">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php else: ?>
                            <span class="disabled">
                                <i class="fas fa-chevron-right"></i>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="empty-state animate-fade">
                    <i class="fas fa-book-open empty-icon"></i>
                    <h3>No Books Found</h3>
                    <p class="empty-text">We couldn't find any books in the <?php echo htmlspecialchars($genre); ?>
                        category. Check back later or browse other genres.</p>
                    <a href="gallery.php" class="btn btn-primary">
                        <i class="fas fa-th-large"></i> Explore Genres
                    </a>
                </div>
            <?php endif; ?>
        </section>
    </main>
    <?php include('includes/footer.php'); ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Intersection Observer for animations
            const animateElements = document.querySelectorAll('.animate-fade, .animate-slide-up');

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = 1;
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, { threshold: 0.1 });

            animateElements.forEach(el => {
                el.style.opacity = 0;
                if (el.classList.contains('animate-slide-up')) {
                    el.style.transform = 'translateY(20px)';
                }
                observer.observe(el);
            });

            // Smooth scroll for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({ behavior: 'smooth' });
                    }
                });
            });

            // Button hover effects
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(btn => {
                btn.addEventListener('mouseenter', function () {
                    this.style.transform = 'translateY(-2px)';
                });

                btn.addEventListener('mouseleave', function () {
                    this.style.transform = 'translateY(0)';
                });
            });

            // Pagination hover effects
            const paginationLinks = document.querySelectorAll('.pagination a:not(.disabled)');
            paginationLinks.forEach(link => {
                link.addEventListener('mouseenter', function () {
                    if (!this.classList.contains('current')) {
                        this.style.transform = 'translateY(-2px)';
                    }
                });

                link.addEventListener('mouseleave', function () {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>

</html>

<?php
$stmt->close();
$count_stmt->close();
$conn->close();
?>