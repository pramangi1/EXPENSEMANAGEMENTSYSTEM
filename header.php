<style>
  header {

    padding: 15px 30px;
    display: flex;
    align-items: center;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    z-index: 999;
  }

  .branding {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 24px;
    font-weight: bold;
    color: rgb(24, 150, 35);
  }

  .branding img.logo {
    height: 32px;
    width: 32px;
    object-fit: contain;
  }

  /* add top padding to body so content isn't hidden under header */
  body {
    padding-top: 70px; /* adjust based on header height */
  }
</style>

<header>
  <div class="branding">
    <img src="image/expenseslogo1.png" alt="Logo" class="logo">
    <span>Budget Buddy</span>
  </div>
</header>
