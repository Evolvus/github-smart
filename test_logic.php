<?php
require_once(__DIR__ . '/config/app.php');

// Test the SYNECA issue assignment logic
$testIssues = [
    [
        'number' => 15,
        'title' => '[SYNECA]:  Implement workspace delete as per Figma design',
        'node_id' => 'I_kwDOOprAO869WXn2'
    ],
    [
        'number' => 1,
        'title' => '[SYNECA]: syneca cli',
        'node_id' => 'I_kwDOOprAO869WTrT'
    ],
    [
        'number' => 100,
        'title' => 'Not a SYNECA issue',
        'node_id' => 'I_kwDOOprAO869WXXX'
    ]
];

foreach ($testIssues as $issue) {
    $projectData = null;
    
    // For SYNECA issues, always assign to SYNECA ROADMAP project
    if (strpos($issue['title'], '[SYNECA]:') === 0) {
        $projectData = [
            'id' => 'PVT_kwDODTts384A-eBm',
            'title' => 'SYNECA ROADMAP',
            'closed' => false,
            'url' => 'https://github.com/orgs/Syneca/projects/1'
        ];
        echo "Assigning issue #{$issue['number']} to SYNECA ROADMAP project based on title\n";
    } else {
        echo "Issue #{$issue['number']} is not a SYNECA issue: {$issue['title']}\n";
    }
    
    if ($projectData) {
        echo "  Project: {$projectData['title']}\n";
    } else {
        echo "  Project: None\n";
    }
    echo "\n";
}
?> 