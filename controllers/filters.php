<?php
?>
<!-- Блок фильтров -->
<form method="get" action="">
  <!-- Поле поиска вакансии -->
  <label class="search-field">
    Поиск вакансии: 
    <input
      type="text"
      name="vacancy"
      value="<?php echo htmlspecialchars($filter_vacancy ?? ''); ?>"
      placeholder="Введите название вакансии"
    />
  </label>

  <!-- Компания -->
  <label>
    Компания:
    <select name="company" title="Выберите компанию" onchange="this.form.submit()">
      <option value="">-- Без фильтрации --</option>
      <?php if (isset($company_map)): ?>
        <?php foreach ($company_map as $original => $shortened): ?>
          <option value="<?php echo htmlspecialchars($original); ?>"
            <?php if (($filter_company ?? '') == $original) echo 'selected'; ?> 
            title="<?php echo htmlspecialchars($original); ?>">
            <?php echo htmlspecialchars(truncate_string($shortened, 50)); ?>
          </option>
        <?php endforeach; ?>
      <?php endif; ?>
    </select>
  </label>

  <!-- Отрасль -->
  <label>
    Отрасль:
    <select name="industry" title="Выберите отрасль" onchange="this.form.submit()">
      <option value="">-- Без фильтрации --</option>
      <?php if (isset($industry_list)): ?>
        <?php foreach ($industry_list as $val): ?>
          <option value="<?php echo htmlspecialchars($val); ?>" 
            <?php if (($filter_industry ?? '') == $val) echo 'selected'; ?> 
            title="<?php echo htmlspecialchars($val); ?>">
            <?php echo htmlspecialchars($val); ?>
          </option>
        <?php endforeach; ?>
      <?php endif; ?>
    </select>
  </label>

  <!-- Тип занятости -->
  <label>
    Тип занятости:
    <select name="schedule" title="Выберите тип занятости" onchange="this.form.submit()">
      <option value="">-- Без фильтрации --</option>
      <?php if (isset($schedule_list)): ?>
        <?php foreach ($schedule_list as $val): ?>
          <option value="<?php echo htmlspecialchars($val); ?>" 
            <?php if (($filter_schedule ?? '') == $val) echo 'selected'; ?> 
            title="<?php echo htmlspecialchars($val); ?>">
            <?php echo htmlspecialchars($val); ?>
          </option>
        <?php endforeach; ?>
      <?php endif; ?>
    </select>
  </label>

  <!-- Опыт работы -->
  <label>
    Опыт работы:
    <select name="experience" title="Выберите опыт работы" onchange="this.form.submit()">
      <option value="">-- Без фильтрации --</option>
      <?php if (isset($experience_list)): ?>
        <?php foreach ($experience_list as $exp): ?>
          <option value="<?php echo htmlspecialchars($exp); ?>"
            <?php if (($filter_experience ?? '') == $exp) echo 'selected'; ?>>
            <?php echo htmlspecialchars($exp); ?>
          </option>
        <?php endforeach; ?>
      <?php endif; ?>
    </select>
  </label>

  <!-- Образование -->
  <label>
    Образование:
    <select name="education" title="Выберите образование" onchange="this.form.submit()">
      <option value="">-- Без фильтрации --</option>
      <?php if (isset($education_list)): ?>
        <?php foreach ($education_list as $val): ?>
          <option value="<?php echo htmlspecialchars($val); ?>"
            <?php if (($filter_education ?? '') == $val) echo 'selected'; ?> 
            title="<?php echo htmlspecialchars($val); ?>">
            <?php echo htmlspecialchars(truncate_string($val, 50)); ?>
          </option>
        <?php endforeach; ?>
      <?php endif; ?>
    </select>
  </label>

  <!-- Муниципальный район -->
  <label style="margin-left:20px;">
    Муниципальный район:
    <select name="district" title="Выберите муниципальный район" onchange="this.form.submit()">
      <option value="">-- Без фильтрации --</option>
      <?php if (isset($district_list)): ?>
        <?php foreach ($district_list as $district): ?>
          <option value="<?php echo htmlspecialchars($district); ?>"
            <?php if (($filter_district ?? '') == $district) echo 'selected'; ?>>
            <?php echo htmlspecialchars($district); ?>
          </option>
        <?php endforeach; ?>
      <?php endif; ?>
    </select>
  </label>

  <!-- Доступность для инвалидов -->
  <label style="margin-left:20px;">
    <span>от 100 тыс.</span>
    <input type="checkbox" name="salary_min" value="1" onchange="this.form.submit()" <?php if (($filter_salary_min ?? '') == '1') echo 'checked'; ?>>
  </label>

  <!-- Кнопка сброса -->
  <a href="?">Сбросить</a>
</form>
<?php
?>