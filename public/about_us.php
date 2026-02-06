<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>About Us - Ripal Design</title>
    <link rel="icon" href="../assets/images/Logo.png" type="image/png">
    <link href="https://fonts.googleapis.com" rel="preconnect" />
    <link crossorigin href="https://fonts.gstatic.com" rel="preconnect" />
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
      :root { --bs-primary: #731209; }
      .navbar.py-2 { padding-top: .35rem; padding-bottom: .35rem; }
      .btn-primary { background-color: var(--bs-primary); border-color: var(--bs-primary); }
      .text-primary { color: var(--bs-primary) !important; }
      .navbar-brand img { max-width: 60px !important; max-height: 60px !important; height: auto !important; width: auto !important; }
    </style>
    <link rel="stylesheet" href="../styles.css">
  </head>
  <body class="bg-white text-dark">
    <nav class="navbar navbar-light bg-white border-bottom fixed-top py-2">
      <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="#">
          <img src="../assets/Content/Logo.png" alt="Ripal Design" height="40" />
          <div class="d-none d-sm-block">
            <div class="fw-semibold text-primary">Ripal Design</div>
            <div class="small text-muted">Design · Execution · Delivery</div>
          </div>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNav" aria-controls="mobileNav">
          <span class="navbar-toggler-icon"></span>
        </button>
        <!-- Nav links are available inside offcanvas (hamburger) to keep navbar compact -->
      </div>
    </nav>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="mobileNav" aria-labelledby="mobileNavLabel">
      <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="mobileNavLabel">Menu</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>
      <div class="offcanvas-body d-flex flex-column align-items-center justify-content-center gap-3">
        <a class="h4" href="/index.html">Home</a>
        <a class="h4 fw-bold" href="about_us.html">About</a>
        <a class="h4" href="/public/services.html">Services</a>
        <a class="h4" href="/public/contact_us.html">Contact</a>
      </div>
    </div>

    <main class="pt-5">
      <section class="vh-100 d-flex align-items-center position-relative overflow-hidden" style="background:linear-gradient(rgba(0,0,0,.45), rgba(0,0,0,.45)), url('../assets/Content/sample%20img%201.jpeg') center/cover no-repeat;">
        <div class="container text-center text-white">
          <h1 class="display-4 fw-light">About Ripal Design</h1>
          <p class="text-uppercase small tracking-wide">Visionary Architecture &amp; Delivery</p>
          <div class="mt-4 d-inline-flex gap-2">
            <a href="/public/project-view.php" class="btn btn-light btn-lg text-primary fw-semibold">View Projects</a>
            <a href="/public/contact_us.html" class="btn btn-outline-light btn-lg">Contact Us</a>
          </div>
        </div>
      </section>

      <section class="py-5">
        <div class="container">
          <div class="row gy-4 align-items-start">
            <div class="col-md-6">
              <h2 class="display-6 mb-3">Our Story</h2>
              <p class="text-muted">Founded in 2017 by two brothers — a designer and a builder — Ripal Design bridges creative ambition with practical delivery. We specialize in projects that require both visionary design and rigorous execution.</p>
              <p class="text-muted">Our combined experience across municipal, institutional and private works ensures designs that stand up to real-world constraints while remaining beautiful and timeless.</p>
            </div>
            <div class="col-md-6">
              <div class="card mb-3 shadow-sm">
                <div class="card-body">
                  <h3 class="h5 text-primary">The Power of Duality</h3>
                  <p class="fst-italic text-muted">"We eliminate the gap between a drawing and a building."</p>
                  <ul>
                    <li>Design leadership for municipal and private projects</li>
                    <li>On-the-ground delivery for government infrastructure</li>
                    <li>Regulatory expertise and construction oversight</li>
                  </ul>
                </div>
              </div>
              <div class="d-flex gap-3">
                <div class="flex-fill p-3 bg-white rounded shadow text-center">
                  <div class="h3 mb-0">50+</div>
                  <div class="small text-muted">Projects</div>
                </div>
                <div class="flex-fill p-3 bg-white rounded shadow text-center">
                  <div class="h3 mb-0">6+</div>
                  <div class="small text-muted">Years</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="bg-light py-5">
        <div class="container">
          <h2 class="h3 mb-4">Values</h2>
          <div class="row g-4">
            <div class="col-md-4">
              <div class="p-4 bg-white rounded shadow-sm h-100">
                <h3 class="h6 text-primary">Collaborative</h3>
                <p class="text-muted small">We partner closely with clients and stakeholders to align design intent and delivery.</p>
              </div>
            </div>
            <div class="col-md-4">
              <div class="p-4 bg-white rounded shadow-sm h-100">
                <h3 class="h6 text-primary">Compliant</h3>
                <p class="text-muted small">Our deep knowledge of regulations reduces approval risk and keeps projects moving.</p>
              </div>
            </div>
            <div class="col-md-4">
              <div class="p-4 bg-white rounded shadow-sm h-100">
                <h3 class="h6 text-primary">Intentional</h3>
                <p class="text-muted small">We design with purpose—durable, functional and context-sensitive outcomes.</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="py-5">
        <div class="container">
          <h2 class="h3 mb-4">Leadership</h2>
          <div class="row g-4">
            <div class="col-md-6">
              <div class="d-flex gap-3 bg-white p-3 rounded shadow-sm align-items-center">
                <img src="../assets/Content/sample%20img%202.jpeg" alt="Mayank" class="rounded" style="width:80px;height:80px;object-fit:cover;" />
                <div>
                  <div class="fw-semibold">Mayank</div>
                  <div class="small text-muted">Head of Design &amp; Innovation</div>
                  <p class="mb-0 text-muted small">Leads our creative direction and ensures design excellence across projects.</p>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="d-flex gap-3 bg-white p-3 rounded shadow-sm align-items-center">
                <img src="../assets/Content/sample%20img%204.jpeg" alt="Dhaval" class="rounded" style="width:80px;height:80px;object-fit:cover;" />
                <div>
                  <div class="fw-semibold">Dhaval</div>
                  <div class="small text-muted">Head of Execution &amp; Infrastructure</div>
                  <p class="mb-0 text-muted small">Responsible for site delivery, compliance, and project management.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="py-5">
        <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between gap-3">
          <div>
            <h3 class="h5">Ready to build something iconic?</h3>
            <p class="text-muted small">Tell us about your brief and timelines — we’ll respond with a plan and next steps.</p>
          </div>
          <div>
            <a href="/public/contact_us.html" class="btn btn-primary">Contact Our Team</a>
          </div>
        </div>
      </section>
    </main>

    <?php include __DIR__ . '/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="" crossorigin="anonymous"></script>
  </body>
</html>
