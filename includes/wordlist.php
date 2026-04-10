<?php
// Wordlist for the local moderation fallback. Matches are case-insensitive
// substring matches after normalizing whitespace and punctuation, so
// variations like "sh!t" or "s h i t" still hit.
//
// Teachers: feel free to edit this list. Keep entries lowercase.
// The goal is age-appropriate content for 4th-5th graders — err on the side
// of blocking. If a kid gets a false positive they can just try different words.

$BLOCKED_WORDS = [
    // Profanity / mild
    'damn','hell','crap','suck','sucks','sucked','shut up','stupid','idiot',
    'dumb','moron','loser','freak','jerk','butt','fart','poop','pee','piss',
    // Profanity / strong (partial list — normalizer strips spaces/symbols)
    'fuck','fucker','fucking','fuk','fck','shit','shite','sh1t','bitch','b1tch',
    'bastard','asshole','arsehole','ass','arse','dick','cock','prick','pussy',
    'cunt','twat','wanker','bollocks','bugger',
    // Slurs (common enough to need explicit blocking — this is not exhaustive)
    'nigger','nigga','faggot','fag','retard','retarded','tranny','dyke',
    'spic','chink','gook','kike','wetback','cracker','coon','sand nigger',
    // Sexual
    'sex','sexy','porn','porno','nude','nudes','naked','horny','boobs','boob',
    'tits','titty','penis','vagina','dildo','condom','orgasm','masturbate',
    'blowjob','handjob','anal','rape','raped','rapist','incest','pedophile','pedo',
    // Violence / weapons
    'kill','killing','killed','murder','murdered','suicide','suicidal','die',
    'death','blood','bloody','gore','gun','guns','rifle','pistol','shotgun',
    'bomb','bombs','bomber','explode','explosion','grenade','knife','stab',
    'stabbed','shoot','shooting','shooter','terrorist','terrorism','isis',
    'behead','execute','hang','lynch','torture',
    // Drugs / alcohol
    'drugs','drug','cocaine','heroin','meth','meth lab','weed','marijuana',
    'cannabis','bong','joint smoke','crack','crackhead','lsd','ecstasy',
    'mdma','xanax','oxy','oxycontin','fentanyl','beer','wine','vodka',
    'whiskey','rum','drunk','drunken','alcoholic','cigarette','cigarettes',
    'smoking','vape','vaping','nicotine',
    // Self harm
    'cut myself','cutting myself','self harm','self-harm','end my life',
    'kill myself','hate myself','worthless',
    // Hate / bullying phrases
    'hate you','i hate','shut your mouth','go die','kys',
    // Gambling
    'gambling','casino','poker chips','blackjack bet',
];
