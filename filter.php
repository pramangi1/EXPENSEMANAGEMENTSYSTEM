<style>
    .filter-container {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 20px;
        margin-left: 70px; /* Adjust this to align with your table */
    }

    .filter-container label {
        font-weight: bold ;
        font-size: 16px;
    }

    .filter-container select,
    .filter-container button {
        padding: 8px 12px;
        font-size: 16px;
        border-box:black;
    
    }
</style>

<div class="filter-container">
    <form method="GET" action="list.php" style="display: flex; align-items: center; gap: 10px;">
        <label for="month">Month:</label>
        <select name="month" id="month">
            <option value="01" <?php echo (isset($_GET['month']) && $_GET['month'] == '01') ? 'selected' : ''; ?>>January</option>
            <option value="02" <?php echo (isset($_GET['month']) && $_GET['month'] == '02') ? 'selected' : ''; ?>>February</option>
            <option value="03" <?php echo (isset($_GET['month']) && $_GET['month'] == '03') ? 'selected' : ''; ?>>March</option>
            <option value="04" <?php echo (isset($_GET['month']) && $_GET['month'] == '04') ? 'selected' : ''; ?>>April</option>
            <option value="05" <?php echo (isset($_GET['month']) && $_GET['month'] == '05') ? 'selected' : ''; ?>>May</option>
            <option value="06" <?php echo (isset($_GET['month']) && $_GET['month'] == '06') ? 'selected' : ''; ?>>June</option>
            <option value="07" <?php echo (isset($_GET['month']) && $_GET['month'] == '07') ? 'selected' : ''; ?>>July</option>
            <option value="08" <?php echo (isset($_GET['month']) && $_GET['month'] == '08') ? 'selected' : ''; ?>>August</option>
            <option value="09" <?php echo (isset($_GET['month']) && $_GET['month'] == '09') ? 'selected' : ''; ?>>September</option>
            <option value="10" <?php echo (isset($_GET['month']) && $_GET['month'] == '10') ? 'selected' : ''; ?>>October</option>
            <option value="11" <?php echo (isset($_GET['month']) && $_GET['month'] == '11') ? 'selected' : ''; ?>>November</option>
            <option value="12" <?php echo (isset($_GET['month']) && $_GET['month'] == '12') ? 'selected' : ''; ?>>December</option>
        </select>

        <label for="year">Year:</label>
        <select name="year" id="year">
            <?php
            $current_year = date("Y");
            for ($i = $current_year; $i >= 2000; $i--) {
                echo "<option value='$i' " . (isset($_GET['year']) && $_GET['year'] == $i ? 'selected' : '') . ">$i</option>";
            }
            ?>
        </select>

        <button type="submit">Filter</button>
    </form>
</div>
