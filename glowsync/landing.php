<?php
require_once 'config.php';
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GlowSync — Sales &amp; Customer Support for Beauty Brands</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,500;0,9..144,600;1,9..144,500&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
<style>
  :root{
    --cream:#FBF5F0;
    --cream-deep:#F3E9DF;
    --ink:#241627;
    --dusk:#1B1121;
    --orchid:#9C27B0;
    --orchid-deep:#7B1FA2;
    --gold:#F0B429;
    --line:rgba(36,22,39,.12);
    --muted:#7A6B7C;
  }
  *{box-sizing:border-box;margin:0;padding:0;}
  html{scroll-behavior:smooth;}
  body{
    font-family:'Inter',sans-serif;
    background:var(--cream);
    color:var(--ink);
    -webkit-font-smoothing:antialiased;
  }
  .eyebrow{
    font-family:'IBM Plex Mono',monospace;
    font-size:12px;
    letter-spacing:.14em;
    text-transform:uppercase;
    color:var(--orchid-deep);
    font-weight:500;
  }
  a{color:inherit;text-decoration:none;}
  img{max-width:100%;display:block;}

  /* ---------- Nav ---------- */
  .nav{
    display:flex;align-items:center;justify-content:space-between;
    padding:22px 6vw;position:sticky;top:0;z-index:40;
    background:rgba(251,245,240,.86);backdrop-filter:blur(10px);
    border-bottom:1px solid transparent;
    transition:border-color .2s;
  }
  .nav.scrolled{border-bottom-color:var(--line);}
  .nav .brand{display:flex;align-items:center;gap:10px;}
  .nav .brand img{width:34px;height:34px;}
  .nav .brand span{font-family:'Fraunces',serif;font-weight:600;font-size:20px;}
  .nav .links{display:flex;align-items:center;gap:28px;}
  .nav .links a.navlink{font-size:14px;font-weight:500;color:var(--muted);}
  .nav .links a.navlink:hover{color:var(--ink);}
  .nav .cta-group{display:flex;gap:10px;}
  .btn{
    display:inline-flex;align-items:center;justify-content:center;
    padding:11px 22px;border-radius:999px;font-weight:600;font-size:14px;
    border:1px solid transparent;cursor:pointer;transition:.18s;
  }
  .btn-primary{background:var(--ink);color:var(--cream);}
  .btn-primary:hover{background:var(--orchid-deep);}
  .btn-ghost{border-color:var(--line);color:var(--ink);}
  .btn-ghost:hover{border-color:var(--ink);}

  /* ---------- Hero ---------- */
  .hero{
    display:grid;grid-template-columns:1.1fr .9fr;gap:40px;
    align-items:center;
    padding:80px 6vw 100px;
    max-width:1360px;margin:0 auto;
  }
  .hero h1{
    font-family:'Fraunces',serif;
    font-weight:500;
    font-size:clamp(38px,4.6vw,64px);
    line-height:1.04;
    letter-spacing:-.01em;
    margin:18px 0 22px;
  }
  .hero h1 em{font-style:italic;color:var(--orchid-deep);}
  .hero p.lede{
    font-size:17px;line-height:1.6;color:var(--muted);
    max-width:460px;margin-bottom:32px;
  }
  .hero .cta-group{display:flex;gap:12px;align-items:center;margin-bottom:14px;}
  .hero .fine{font-family:'IBM Plex Mono',monospace;font-size:11.5px;color:var(--muted);letter-spacing:.02em;}

  /* Glow meter — signature element */
  .glow-wrap{position:relative;display:flex;align-items:center;justify-content:center;}
  .glow-card{
    position:relative;width:100%;max-width:380px;aspect-ratio:1/1;
    border-radius:28px;
    background:linear-gradient(160deg,#2A1830 0%, var(--dusk) 100%);
    box-shadow:0 30px 70px -20px rgba(36,22,39,.45);
    display:flex;align-items:center;justify-content:center;
    overflow:hidden;
  }
  .glow-card::before{
    content:'';position:absolute;inset:-40%;
    background:radial-gradient(circle at 50% 50%, rgba(240,180,41,.35), rgba(156,39,176,.28) 45%, transparent 70%);
    filter:blur(30px);
    animation:drift 9s ease-in-out infinite alternate;
  }
  @keyframes drift{
    0%{transform:translate(-4%,-2%) scale(1);}
    100%{transform:translate(4%,3%) scale(1.08);}
  }
  .dial{position:relative;width:220px;height:220px;}
  .dial svg{width:100%;height:100%;transform:rotate(-90deg);}
  .dial-track{fill:none;stroke:rgba(255,255,255,.12);stroke-width:10;}
  .dial-fill{
    fill:none;stroke:url(#glowGrad);stroke-width:10;stroke-linecap:round;
    stroke-dasharray:597;stroke-dashoffset:597;
    animation:fillDial 1.8s cubic-bezier(.22,.9,.3,1) .3s forwards;
  }
  @keyframes fillDial{to{stroke-dashoffset:88;}}
  .dial-label{
    position:absolute;inset:0;display:flex;flex-direction:column;
    align-items:center;justify-content:center;color:#fff;text-align:center;
  }
  .dial-label .num{font-family:'Fraunces',serif;font-size:44px;font-weight:500;}
  .dial-label .cap{font-family:'IBM Plex Mono',monospace;font-size:11px;letter-spacing:.1em;color:rgba(255,255,255,.65);text-transform:uppercase;margin-top:4px;}
  .glow-caption{
    position:absolute;bottom:22px;left:22px;right:22px;
    display:flex;justify-content:space-between;align-items:center;
    font-family:'IBM Plex Mono',monospace;font-size:11px;color:rgba(255,255,255,.55);
  }
  .glow-caption .dot{width:6px;height:6px;border-radius:50%;background:var(--gold);display:inline-block;margin-right:6px;box-shadow:0 0 8px var(--gold);}

  /* ---------- Sections shared ---------- */
  section{padding:0 6vw;}
  .section-inner{max-width:1200px;margin:0 auto;}
  .section-head{max-width:560px;margin-bottom:44px;}
  .section-head .eyebrow{margin-bottom:10px;display:block;}
  .section-head h2{
    font-family:'Fraunces',serif;font-weight:500;font-size:clamp(26px,3vw,36px);
    line-height:1.15;letter-spacing:-.01em;
  }

  /* ---------- Features ---------- */
  .features{padding-top:40px;padding-bottom:90px;}
  .feature-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1px;background:var(--line);border:1px solid var(--line);border-radius:20px;overflow:hidden;}
  .feature{background:var(--cream);padding:32px 26px;}
  .feature .verb{font-family:'IBM Plex Mono',monospace;font-size:11px;letter-spacing:.1em;text-transform:uppercase;color:var(--gold);background:var(--ink);display:inline-block;padding:4px 10px;border-radius:6px;margin-bottom:18px;}
  .feature h3{font-family:'Fraunces',serif;font-weight:500;font-size:19px;margin-bottom:8px;}
  .feature p{font-size:13.5px;color:var(--muted);line-height:1.55;}

  /* ---------- Showcase (product mock) ---------- */
  .showcase{padding-bottom:100px;}
  .showcase-panel{
    background:var(--dusk);border-radius:28px;padding:56px 6vw;
    display:grid;grid-template-columns:.85fr 1.15fr;gap:48px;align-items:center;
    color:var(--cream);position:relative;overflow:hidden;
  }
  .showcase-panel::before{
    content:'';position:absolute;top:-30%;right:-10%;width:60%;height:160%;
    background:radial-gradient(circle, rgba(156,39,176,.35), transparent 65%);
  }
  .showcase-text{position:relative;z-index:1;}
  .showcase-text h2{font-family:'Fraunces',serif;font-weight:500;font-size:clamp(24px,2.6vw,32px);line-height:1.2;margin-bottom:16px;}
  .showcase-text p{color:rgba(251,245,240,.7);font-size:14.5px;line-height:1.65;margin-bottom:24px;max-width:400px;}
  .mini-list{display:flex;flex-direction:column;gap:12px;}
  .mini-list li{list-style:none;display:flex;align-items:center;gap:10px;font-size:13.5px;color:rgba(251,245,240,.85);}
  .mini-list .check{width:18px;height:18px;border-radius:50%;background:var(--gold);color:var(--dusk);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0;}

  .mock{position:relative;z-index:1;background:var(--cream);border-radius:16px;padding:18px;box-shadow:0 30px 60px -15px rgba(0,0,0,.5);transform:rotate(-1.2deg);}
  .mock-row{display:flex;justify-content:space-between;align-items:center;padding:10px 8px;border-bottom:1px solid var(--line);font-size:12.5px;}
  .mock-row:last-child{border-bottom:none;}
  .mock-row .name{font-weight:600;}
  .mock-row .tag{font-family:'IBM Plex Mono',monospace;font-size:10px;padding:3px 8px;border-radius:20px;letter-spacing:.03em;}
  .tag-done{background:#dcfce7;color:#16a34a;}
  .tag-pend{background:#fee2e2;color:#dc2626;}
  .tag-proc{background:#dbeafe;color:#1d4ed8;}

  /* ---------- CTA ---------- */
  .cta-band{padding:100px 6vw 110px;text-align:center;}
  .cta-band h2{
    font-family:'Fraunces',serif;font-weight:500;font-size:clamp(30px,4vw,48px);
    line-height:1.1;letter-spacing:-.01em;margin-bottom:20px;
  }
  .cta-band h2 em{font-style:italic;color:var(--orchid-deep);}
  .cta-band p{color:var(--muted);font-size:15.5px;margin-bottom:32px;}

  /* ---------- Footer ---------- */
  footer{
    border-top:1px solid var(--line);padding:30px 6vw;
    display:flex;justify-content:space-between;align-items:center;
    font-size:13px;color:var(--muted);
  }
  footer .brand{display:flex;align-items:center;gap:8px;font-family:'Fraunces',serif;color:var(--ink);font-weight:600;}
  footer .brand img{width:22px;height:22px;}

  @media(max-width:900px){
    .hero{grid-template-columns:1fr;padding-top:50px;}
    .feature-grid{grid-template-columns:1fr 1fr;}
    .showcase-panel{grid-template-columns:1fr;}
    .nav .links{display:none;}
  }
  @media(max-width:560px){
    .feature-grid{grid-template-columns:1fr;}
  }
  @media (prefers-reduced-motion: reduce){
    .glow-card::before{animation:none;}
    .dial-fill{animation:none;stroke-dashoffset:88;}
  }
</style>
</head>
<body>

<nav class="nav" id="nav">
  <a href="index.php" class="brand">
    <img src="glowcode.png" alt="GlowSync">
    <span>GlowSync</span>
  </a>
  <div class="links">
    <a href="#features" class="navlink">Features</a>
    <a href="#product" class="navlink">Product</a>
  </div>
  <div class="cta-group">
    <a href="login.php" class="btn btn-ghost">Sign in</a>
    <a href="signup.php" class="btn btn-primary">Get started</a>
  </div>
</nav>

<section class="hero">
  <div>
    <span class="eyebrow">Sales &amp; customer support, in one place</span>
    <h1>Every order, every<br>customer, kept in <em>sync</em>.</h1>
    <p class="lede">GlowSync brings your sales pipeline, customer profiles, and support tickets together — built for beauty brands who'd rather spend time on their customers than on spreadsheets.</p>
    <div class="cta-group">
      <a href="signup.php" class="btn btn-primary">Create free account</a>
      <a href="login.php" class="btn btn-ghost">Sign in →</a>
    </div>
    <div class="fine">No credit card required · Set up in minutes</div>
  </div>

  <div class="glow-wrap">
    <div class="glow-card">
      <div class="dial">
        <svg viewBox="0 0 220 220">
          <defs>
            <linearGradient id="glowGrad" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" stop-color="#9C27B0"/>
              <stop offset="100%" stop-color="#F0B429"/>
            </linearGradient>
          </defs>
          <circle class="dial-track" cx="110" cy="110" r="95"/>
          <circle class="dial-fill" cx="110" cy="110" r="95"/>
        </svg>
        <div class="dial-label">
          <div class="num">86%</div>
          <div class="cap">Repeat customers</div>
        </div>
      </div>
      <div class="glow-caption">
        <span><span class="dot"></span>Live</span>
        <span>glowsync.app</span>
      </div>
    </div>
  </div>
</section>

<section class="features" id="features">
  <div class="section-inner">
    <div class="section-head">
      <span class="eyebrow">What it does</span>
      <h2>Four jobs, one login.</h2>
    </div>
    <div class="feature-grid">
      <div class="feature">
        <span class="verb">Track</span>
        <h3>Sales &amp; orders</h3>
        <p>Log new orders, follow their status from pending to completed, and see what's selling without leaving the page.</p>
      </div>
      <div class="feature">
        <span class="verb">Know</span>
        <h3>Every customer</h3>
        <p>Full purchase history, membership tier, and notes on every profile — so no conversation starts from zero.</p>
      </div>
      <div class="feature">
        <span class="verb">Support</span>
        <h3>Open tickets</h3>
        <p>Reply to customer issues in a real conversation thread and change ticket status as you go.</p>
      </div>
      <div class="feature">
        <span class="verb">Grow</span>
        <h3>Sales reports</h3>
        <p>Daily, weekly, and monthly totals with a trend line, so you know where the business is heading.</p>
      </div>
    </div>
  </div>
</section>

<section class="showcase" id="product">
  <div class="section-inner">
    <div class="showcase-panel">
      <div class="showcase-text">
        <span class="eyebrow" style="color:var(--gold);">Inside GlowSync</span>
        <h2 style="margin-top:10px;">Built around the order, not the spreadsheet.</h2>
        <p>Every sale links straight to a customer profile and product record — so a status change on one page shows up everywhere it matters.</p>
        <ul class="mini-list">
          <li><span class="check">✓</span> Customer &amp; order records stay linked automatically</li>
          <li><span class="check">✓</span> Product catalog with photos and live stock counts</li>
          <li><span class="check">✓</span> Support threads tied to the right customer, always</li>
        </ul>
      </div>
      <div class="mock">
        <div class="mock-row"><span class="name">Lynn Alvarado</span><span class="tag tag-done">Completed</span></div>
        <div class="mock-row"><span class="name">Erich Domingo</span><span class="tag tag-pend">Pending</span></div>
        <div class="mock-row"><span class="name">Mary Santiago</span><span class="tag tag-proc">Processing</span></div>
        <div class="mock-row"><span class="name">Vitamin C Serum</span><span class="tag" style="background:#f3e8fb;color:#7b1fa2;">42 in stock</span></div>
      </div>
    </div>
  </div>
</section>

<section class="cta-band">
  <div class="section-inner">
    <h2>Ready to bring it<br><em>all in sync?</em></h2>
    <p>Create your account and start logging orders in the next five minutes.</p>
    <div class="cta-group" style="justify-content:center;">
      <a href="signup.php" class="btn btn-primary">Create free account</a>
      <a href="login.php" class="btn btn-ghost">I already have one</a>
    </div>
  </div>
</section>

<footer>
  <div class="brand"><img src="glowcode.png" alt="">GlowSync</div>
  <div>© <?= date('Y') ?> GlowSync. Sales &amp; customer support management.</div>
</footer>

<script>
  const nav = document.getElementById('nav');
  window.addEventListener('scroll', () => {
    nav.classList.toggle('scrolled', window.scrollY > 8);
  });
</script>
</body>
</html>
