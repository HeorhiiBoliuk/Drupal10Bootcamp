const gulp = require('gulp');
const sass = require('gulp-sass')(require('sass'));
const rename = require('gulp-rename');

gulp.task('sass', function () {
  return gulp.src('../assets/scss/page.scss') // Путь к вашему файлу SCSS
    .pipe(sass().on('error', sass.logError))
    .pipe(rename('page.css')) // Переименуйте скомпилированный CSS файл
    .pipe(gulp.dest('../assets/css')); // Каталог назначения для скомпилированного CSS
});
gulp.task('sass', function () {
  return gulp.src('../assets/scss/top-header.scss') // Путь к вашему файлу SCSS
    .pipe(sass().on('error', sass.logError))
    .pipe(rename('top-header.css')) // Переименуйте скомпилированный CSS файл
    .pipe(gulp.dest('../assets/css')); // Каталог назначения для скомпилированного CSS
});

gulp.task('watch', function () {
  gulp.watch('assets/scss/*.scss', gulp.series('sass')); // Отслеживаем изменения в файлах SCSS
});

gulp.task('default', gulp.series('sass', 'watch'));
