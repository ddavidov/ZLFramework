module.exports = function(grunt) {

	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),

		// Metadata
		meta: {
			pluginPath: 'plg_zlframework',
			buildPath: 'build'
		},

		
		copy: {

			// copy content to Build folder
			main: {
				files: [
					{expand: true, cwd: '<%= meta.pluginPath %>', src: ['**'], dest: '<%= meta.buildPath %>/<%= meta.pluginPath %>'}
				]
			},

			// save ZLUX 1.0 JS scripts as dev uncompressed versions
			dev: {
				files: [
					{
						expand: true, 
						cwd: '<%= meta.buildPath %>/<%= meta.pluginPath %>', 
						src: [
							'**/zlframework/zlux/**/*.js', // all ZLUX plugins
							'!**/*zlux/assets/**/*.js' // discart assets
						],
						dest: '<%= meta.buildPath %>/<%= meta.pluginPath %>',
						ext: '.dev.js'
					}
				]
			},

			// copy zlux to vendor folder
			zlux: {
				files: [
					{
						expand: true,
						cwd: '<%= meta.buildPath %>/zlux/zlux-master/dist', 
						src: ['**'], 
						dest: '<%= meta.pluginPath %>/zlframework/vendor/zlux'
					}
				]
			}
		},
				
		// compress JS files
		uglify: {
			options: {
				banner: '/* ===================================================\n' +
						' * <%= pkg.plugin.name %>\n' +
						' * <%= pkg.plugin.link %>\n' +
						' * ===================================================\n' +
						' * Copyright (C) JOOlanders SL\n' +
						' * http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only\n' +
						' * ========================================================== */\n',
			},
			dist: {
				files: [
					{
						expand: true, 
						cwd: '<%= meta.buildPath %>/<%= meta.pluginPath %>', 
						src: [
							'**/zlframework/**/*.js', // all js
							'!**/*.min.js', // discart min versions
							'!**/vendor/**/*.js' // discart vendor folder
						],
						dest: '<%= meta.buildPath %>/<%= meta.pluginPath %>'
					}
				]
			}
		},

		// download a file
		curl: {
			zlux: {
				src: 'https://github.com/JOOlanders/zlux/archive/master.zip',
				dest: '<%= meta.buildPath %>/zlux/master.zip'
			}
		},

		unzip: {
			zlux: {
				src: '<%= meta.buildPath %>/zlux/master.zip',
				dest: '<%= meta.buildPath %>/zlux'
			}
		},

		// make a zipfile
		compress: {
			main: {
				options: {
					archive: '<%= meta.buildPath %>/plg_zlframework.zip', // .tar.gz, .zip
					mode: 'zip' // tgz, zip
				},
				files: [
					{expand: true, cwd: '<%= meta.buildPath %>/<%= meta.pluginPath %>', src: ['**'], dest: '/'} // makes all src relative to cwd
				]
			}
		},

		// remove temporal build files
		clean: {
			target: {
				src: ['<%= meta.buildPath %>/<%= meta.pluginPath %>']
			}
		}
	});

	// load in Grunt plugins
	grunt.loadNpmTasks('grunt-contrib-copy');
	grunt.loadNpmTasks('grunt-contrib-jshint');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-replacer');
	grunt.loadNpmTasks('grunt-contrib-compress');
	grunt.loadNpmTasks('grunt-contrib-clean');
	grunt.loadNpmTasks('grunt-curl');
	grunt.loadNpmTasks('grunt-zip');

	// register tasks
	grunt.registerTask('default', ['clean', 'copy:main', 'copy:dev', 'compress']);

	grunt.registerTask('dist', ['clean', 'copy:main', 'copy:dev', 'uglify', 'compress']);

	grunt.registerTask('zlux', 'Update vendor zlux', function(repo) {

		// set default repository
		repo = repo === undefined ? 'master' : repo;

		// clean paths
		grunt.config('clean.target.src', 'plg_zlframework/zlframework/vendor/zlux');
		grunt.task.run('clean:target');

		grunt.config('clean.target.src', 'build/zlux');
		grunt.task.run('clean:target');

		// download zlux
		grunt.config('curl.zlux.src', 'https://github.com/JOOlanders/zlux/archive/'+repo+'.zip');
		grunt.task.run('curl:zlux');
		
		// unzip it
		grunt.task.run('unzip:zlux');

		// copy contents
		grunt.task.run('copy:zlux');
	});
};