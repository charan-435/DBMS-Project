<?php
    require_once __DIR__ . "/../../backend/DataService.php";

    $service = new DataService();
    $duos = $service->getActorDirectorCollaborations();
    $topRatedActors = $service->getRatingMagnets();
    $corr = $service->getRatingRevenueCorrelation();
    $cinemaMatch = $service->getCinemaMatch();
    $recentCollabs = $service->getRecentCollaborations();

    $barColors = ['var(--accent-primary)', '#5cd6b6', '#6ea8fe', '#a68dff', '#ff8296'];

    $avatarColors = [
        ['#e8a57e', '#d4845a'], ['#5cd6b6', '#3bb89a'],
        ['#6ea8fe', '#4a8ae0'], ['#a68dff', '#8565e0']
    ];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Cinematic Lens - Genres</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <?php include 'components/sidebar.php' ?>
    
    <main class="main-content">
        <?php include 'components/topbar.php'; ?>

        <div class="page-content">
           
            <div style="margin-bottom: 1.5rem;">
                <p class="text-accent uppercase tracking-wider text-xs">INTELLIGENT REPORT</p>
                <h1 style="font-size: 2.25rem; font-weight: 800;">Creative Alchemy</h1>
                <p class="collab-desc">Analyzing the high-frequency intersections of talent and vision. Our proprietary matrix evaluates how specifice director-actor pairings influence both artistic acclaim and commercial dominance</p>
            </div>
           
            <div class="duo-rating" style="display:flex; gap: 1.4rem;">
                <div class="card duo-rating-content">
                    <div style="justify-content:space-between; margin-bottom:1.25rem;">
                        <h2 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.7rem;">Duo Success Matrix</h2>
                        <p class="text-accent uppercase tracking-wider text-xs">ACTOR-DIRECTOR REVENUE YIELDS</p>
                    </div>
                    <?php 
                        $maxCount = max(array_column($duos,'count'));
                        if($maxCount == 0) $maxCount = 1;
                        
                        $maxRevenue = max(array_column($duos,'avg_revenue'));
                        if($maxRevenue == 0) $maxRevenue = 1;

                        $maxRating = max(array_column($duos,'avg_rating'));
                        if($maxRating == 0) $maxRating = 1;

                        $maxAudiImpact = max(array_column($duos,'aud_impact'));
                        if($maxAudiImpact == 0) $maxAudiImpact = 1;

                        foreach($duos as &$duo){
                            $countWeightage = $duo['count'] / $maxCount;
                            $revenueWeightage = $duo['avg_revenue'] / $maxRevenue;
                            $ratingWeightage = $duo['avg_rating'] / $maxRating;
                            $audImpactWeightage = $duo['aud_impact'] / $maxAudiImpact;
                            $duo['duoSuccess'] = round(($countWeightage * 0.25 +
                                                $revenueWeightage * 0.25 +
                                                $ratingWeightage * 0.20 +
                                                $audImpactWeightage * 0.30) * 100);
                        }
                        unset($duo);

                        usort($duos, function($a, $b) {
                            return $b['duoSuccess'] <=> $a['duoSuccess'];
                        });
                    ?>
                    <div style="display:flex; gap:1rem;">
                        <?php foreach(array_slice($duos, 0, 4) as $index => $duo):
                            $width = $duo['duoSuccess'];
                            $color = 'var(--accent-primary)';
                        ?>
                        <div class="card duo-card">
                            <span class="trend-up duo-card-name" style="font-size: 0.85rem;">
                                <?= htmlspecialchars($duo['director']) ?> + <?= htmlspecialchars($duo['actor']) ?>
                            </span>
                            <div class="region-bar-track">
                                <div class="region-bar-fill" style="width: <?= $width ?>%; background-color: <?= $color ?>;"></div>
                            </div>
                            <div class="duo-card-footer">
                                <span class="duo-percentage">
                                    <?= $duo['duoSuccess'] ?>%
                                </span>
                                <span class="duo-label text-muted" style="font-size: 0.8rem;">Success Rate</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="card duo-metrics" style="display:flex;">
                        <?php
                        $totalRating = 0;
                        $totalCount = 0;

                        foreach ($duos as $d) {
                            $totalRating += $d['avg_rating'] * $d['count'];
                            $totalCount += $d['count'];
                        }
                        $avgRating = $totalCount > 0 ? round($totalRating / $totalCount, 1) : 0;

                        $totalRevenue = 0;
                        $totalRevenueCount = 0;

                        foreach ($duos as $d) {
                            $totalRevenue += $d['avg_revenue'] * $d['count'];
                            $totalRevenueCount += $d['count'];
                        }

                        $avgRevenue = $totalRevenueCount > 0 ? $totalRevenue / $totalRevenueCount : 0;
                        $growthIndex = round(log10($avgRevenue + 1) * 10, 1);

                        $totalImpact = 0;
                        foreach ($duos as $d) {
                            $totalImpact += $d['aud_impact'];
                        }
                        $avgImpact = count($duos) > 0 ? $totalImpact / count($duos) : 0;
                        $audienceImpact = round(min(100, ($avgImpact / 30) * 100));
                        ?>
                        <div style="display:flex; align-items:center; gap:12px;">
                            <div><i class="fa-solid fa-arrow-trend-up"></i></div>
                            <div style="display:flex; flex-direction:column; gap:4px;">
                                <p class="uppercase tracking-wider text-sm" style="color:#f4ae83; font-weight:500;">GROWTH INDEX</p>
                                <p style="font-size: large;"><strong>+<?= $growthIndex ?>%</strong></p>
                                <p class="tracking-wider text-xs" style="color:#c2a18d">Combined Box Office</p>
                            </div>
                        </div>
                        <div style="display:flex; align-items:center; gap:12px;">
                            <div ><i class="fa-solid fa-star" style="color:#5cd6b6"></i></div>
                            <div style="display:flex; flex-direction:column; gap:4px;">
                                <p class="uppercase tracking-wider text-sm" style="color:#5cd6b6; font-weight:500;">AVG RATING</p>
                                <p style="font-size: large;"><strong><?= $avgRating ?>/10</strong></p>
                                <p class="tracking-wider text-xs" style="color:#c2a18d">Director-Actor Collaborative IMDb</p>
                            </div>
                        </div>
                        <div style="display:flex; align-items:center; gap:12px;"> 
                            <div><i class="fa-solid fa-users"></i></div>
                            <div style="display:flex; flex-direction:column; gap:4px;">
                                <p class="uppercase tracking-wider text-sm" style="color:#f4ae83; font-weight:500;">AUDIENCE IMPACT</p>
                                <p style="font-size: large;"><strong> <?= $audienceImpact ?>%</strong></p>
                                <p class="tracking-wider text-xs" style="color:#c2a18d">Based on Votes & Ratings</p>
                            </div>
                        </div>
                    </div>
                </div> 

                <div class="card ratingMagnets-card" style="justify-content: space-between;">
                    <div style="justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                         <h2 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.7rem;">Rating Magnets</h2>
                        <p class="trend-up text-accent uppercase tracking-wider text-xs">CRITICAL CONSISTENCY LEADERS</p>
                    </div>
                    
                    <?php foreach ($topRatedActors as $index => $actor): 
                        $c = $avatarColors[$index % count($avatarColors)];
                    ?>
                    <div class="auteur-item">
                        <div class="actor-info" style="display:flex; gap:0.75rem;">
                            <div class="auteur-avatar" style="background: linear-gradient(135deg, <?= $c[0] ?>, <?= $c[1] ?>);"></div>
                            <div>
                                <div class="font-semibold" style="font-size: 0.9rem;"><?= htmlspecialchars($actor['name']) ?></div>
                                <div class="text-xxs text-muted mt-1">Avg IMDb Rating:<?= $actor['avgRating'] ?></div>
                            </div>
                            <div class="actor-rating <?= $isTop ? 'text-accent' : '' ?>" style="<?= $isTop ? 'font-size: 1.2rem;' : '' ?>">
                                <i class="fa-solid fa-arrow-trend-up"></i>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <div style="margin-top: auto; padding-top: 1rem;">
                        <button class="btn-primary">VIEW FULL TALENT AUDIT</button>
                    </div>
                </div>
            </div>
            <div class="card corrMatrix">
                <div style="justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h2 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.7rem;">The Correlation Matrix</h2>
                    <p class="text-accent uppercase tracking-wider text-xs ">IMDB RATINGS VS GROSS BOX OFFICE (GLOBAL)</p>
                </div>
                
                <?php
                $data = $corr; 
                $blockbuster = [];
                $critical = [];

                foreach ($data as $row) {
                    // echo "<pre>"; print_r($row); echo "</pre>"; // Debugging line   

                    if ($row['y'] <= 0) continue;

                    $point = [
                        'x' => (float)$row['x'],
                        'y' => (float)$row['y']
                    ];

                    if ($row['y'] >= 50000000) {
                        $blockbuster[] = $point;
                    } else {
                        $critical[] = $point;
                    }
                }

        
                echo "Blockbusters: " . count($blockbuster) . "<br>";
                echo "Critical: " . count($critical) . "<br>";

                ?>

               <div class="correlation-card">
                    
                    <canvas id="chart"></canvas>

                    <div class="bottom">
                        <i class="fa-regular fa-lightbulb"></i>
                        <p>Films with ratings between 7.5-8.2 show high variation.</p>
                        <button>Download Dataset</button>
                    </div>
                </div>

                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

                <script>
                const blockbuster = <?= json_encode($blockbuster) ?>;
                const critical = <?= json_encode($critical) ?>;

                new Chart(document.getElementById('chart'), {
                type: 'scatter',
                data: {
                    datasets: [
                    {
                        label: 'Blockbusters',
                        data: blockbuster,
                        backgroundColor: 'rgba(251,191,36,0.6)',
                        borderColor: 'rgba(251,191,36,0.2)',
                        pointRadius: 3,
                        pointHoverRadius: 6
                    },
                    {
                        label: 'Critical Darlings',
                        data: critical,
                        backgroundColor: 'rgba(34,211,238,0.6)',
                        borderColor: 'rgba(34,211,238,0.2)',
                        pointRadius: 3,
                        pointHoverRadius: 6
                    }
                    ]
                },
                options: {
                    animation: false,
                    plugins: {
                    legend: {
                        position: 'top',    
                        align: 'end', 
                        labels: { color: 'white',
                                  usePointStyle: true,
                                  pointStyle: 'circle',
                                  boxWidth: 8,
                                  boxHeight:8 }
                    }},
                    scales: {
                    x: {
                        title: {
                        display: true,
                        text: 'IMDB Rating',
                        color: '#9ca3af'
                        },
                        ticks: { color: 'white' },
                        grid: { color: 'rgba(255,255,255,0.05)' }
                    },
                    y: {
                        type: 'logarithmic',  
                        title: {
                        display: true,
                        text: 'Revenue (log scale)',
                        color: '#9ca3af'
                        },
                        ticks: { color: 'white' },
                        grid: { color: 'rgba(255,255,255,0.05)' }
                    }
                    }
                }
                });
                </script>

            </div>
            <div style="display: flex; gap: 1.4rem; flex:1;">
                <div class="card" style="flex:1;">
                    <div style="justify-content:space-between; margin-bottom:1.25rem;">
                        <h2 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.7rem;">CINEMA SIGNATURE</h2>
                        <p class="text-accent uppercase tracking-wider text-xs">MARKET PENETRATION BY CREATIVE STYLE</p>
                    </div>
                    <?php 
                    $labels = [
                        'pan-india-epic' => 'Pan-India Epic',
                        'emotional-drama' => 'Emotional Drama',
                        'rom-com' => 'Rom-Com Narratives'
                    ];

                    $total = array_sum($cinemaMatch);
                    if($total == 0) $total = 1;
                    $index = 0;
                    foreach($cinemaMatch as $style => $match):
                        $percentage = round(($match / $total) * 100);
                        $width = $percentage;
                        $color = $barColors[$index % count($barColors)];
                        $index++;
                    ?>

                    <div style="margin-bottom: 2rem;">
                        <div class="signature-row flex-row" style="margin-bottom: 0.5rem;">
                            <span class="font-semibold" style="font-size: 0.85rem;"><?= $labels[$style] ?? ucfirst($style) ?></span>
                            <div class="signature-right" style="display:flex; align-items:center; gap:10px">
                                <span class="font-bold " style="font-size: 0.85rem;"><?= $percentage ?>%</span>
            
                                <div class="mini-bar-track"><div class="mini-bar-fill" style="width: <?= $width ?>%; background-color: <?= $color ?>;"></div></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="card">
                    <div style="justify-content:space-between; margin-bottom:1.25rem;">
                        <h2 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.7rem;">Recent Collaborations</h2>
                        <p class="uppercase tracking-wider text-xs" style="color:var(--accent-hover)">SIGNED & HIGH-YIELD CONTRACTS</p>
                    </div>
                    <div class="recentCollab">
                        <table class="data-recentCollab">
                            <thead>
                                <tr>
                                <th>PROJECT</th>
                                <th>CAST/DIRECTOR</th>
                                <th>YIELD PROB</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentCollabs as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['project']) ?></td>
                                    <td><?= htmlspecialchars($item['collab']) ?></td>
                                    <td class="trend-up"><?= $item['yield_prob'] ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>  
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>