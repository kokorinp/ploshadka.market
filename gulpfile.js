const gulp = require('gulp');
const sass = require('gulp-sass')(require('sass'));
const brS = require('browser-sync').create();

gulp.task('sass', function (done){
   gulp.src('./html/scss/**/*.scss')
       .pipe(sass())
       .pipe(gulp.dest('./html/css/'));
   done();
});

gulp.task('serv', function (){
   brS.init({
      server:{
         baseDir: './html/'
      },
      port: 38080
   });
   gulp.watch('./**/*').on('change', brS.reload);
});

gulp.task('sass:watch', function (){
   gulp.watch('./html/scss/**/*.scss', gulp.series('sass'));
});

gulp.task('default', gulp.parallel('sass:watch', 'serv'));
