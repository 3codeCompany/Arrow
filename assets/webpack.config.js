const webpack = require('webpack')
const path = require('path');
const ExtractTextPlugin = require('extract-text-webpack-plugin');
const {resolve} = require('path');
const publicPath = '/vendor/arrow/engine/assets/dist/';
const AssetsPlugin = require('assets-webpack-plugin')
const BundleAnalyzerPlugin = require('webpack-bundle-analyzer').BundleAnalyzerPlugin;
const CleanWebpackPlugin = require('clean-webpack-plugin');
const fs = require('fs');
var chokidar = require('chokidar');

let watchedDirs = [
    {package: 'app', dir: resolve(__dirname, './../../../../app/views')},
    {package: 'access', dir: resolve(__dirname, './../src/packages/access/views')}
]


var walk = function (dir) {
    var results = []
    var list = fs.readdirSync(dir)
    list.forEach(function (file) {
        file = dir + '/' + file
        var stat = fs.statSync(file)
        if (stat && stat.isDirectory()) results = results.concat(walk(file))
        else {
            if (file.match(/.*\.component\.js$/)) {

                let name = file.replace(dir, "");
                name = name.replace(/\//g, "_");
                name = name.replace(".component.js", "");
                results.push({
                    name: name,
                    path: file
                });
            }
        }
        ;
    })
    return results
}

// One-liner for current directory, ignores .dotfiles
module.exports = function (env = {}) {
    env.production = typeof(env.production) != "undefined" ? env.production : false;
    env.watch = typeof(env.watch) != "undefined" ? env.watch : false;

    const linkArrowDir = () => {
        let FileContent = "";
        watchedDirs.map(config => {
            walk(config.dir).forEach((entry) => {
                let name = entry.path.replace(config.dir + "/", "");
                name = name.replace(/\//g, "_");
                name = name.replace(".component.js", "");
                name = config.package + "_" + name;
                FileContent += "import " + name + " from '" + entry.path.replace(/\\/g, "\\\\") + "';\n";
                FileContent += "ReactHelper.register('" + name + "', " + name + ");\n";
            });
        });

        fs.writeFile("./build/components.include.js", FileContent, function (err) {
                if (err) {
                    return console.log(err);
                }
                console.log("The file was saved!");
            });
    }
    linkArrowDir();

    if (!env.production) {
        let dirs = watchedDirs.reduce((p,c)=>{ p.push(c.dir); return p;}, []);
        console.log(dirs);


        chokidar.watch(
            dirs,
            {
                ignored: /(^|[\/\\])\../,
                ignoreInitial: true

            })
            .on('add', (event, path) => {
                console.log(event, path);
                linkArrowDir();
            }).on('unlink', (event, path) => {
            console.log(event, path);
            linkArrowDir();
        });
    }


    var conf = {

        context: resolve(__dirname, ''),
        output: {
            filename: 'bundle.js',
            path: path.resolve(__dirname, env.production ? 'dist/' : 'public/'),
            publicPath: 'http://localhost:3000/',

        },

        //devtool: env.production ? 'source-map' : 'source-map', //
        devtool: "#cheap-module-eval-source-map",


        module: {
            loaders: [
                {
                    test: [/\.js$/, /\.es6$/],
                    exclude: /node_modules/,

                    //loader: 'babel-loader',
                    //dodatkowe ustawienia potrzebne aby babel działał out of home dir ( inaczej nie parsował plików z zewnątrz)
                    loader: 'babel-loader?babelrc=false&extends=' + require('path').join(__dirname, '/.babelrc') + "&retainLines=true"


                },
                {test: /\.css/, loader: 'style-loader!css-loader'},
                {
                    test: /\.(jpe?g|png|gif|svg)$/i,
                    loaders: [
                        'file-loader?hash=sha512&digest=hex&name=./cache/[hash].[ext]',
                        {
                            loader: 'image-webpack-loader',
                            query: {
                                mozjpeg: {
                                    progressive: true,
                                },
                                gifsicle: {
                                    interlaced: false,
                                },
                                optipng: {
                                    optimizationLevel: 4,
                                },
                                pngquant: {
                                    quality: '75-90',
                                    speed: 3,
                                },
                            }
                        }
                    ]
                },
                {test: /\.woff(2)?(\?v=[0-9]\.[0-9]\.[0-9])?$/, loader: 'url-loader?limit=10000&mimetype=application/font-woff&name=/cache/[hash].[ext]'},
                {test: /\.(ttf|eot|svg)(\?v=[0-9]\.[0-9]\.[0-9])?$/, loader: 'file-loader?name=/cache/[hash].[ext]'}
            ]
        },
        resolve: {
            extensions: ['.js', '.es6'],
            unsafeCache: false,
            modules: [
                path.resolve(__dirname, './node_modules'),
                path.resolve(__dirname, './src'),

            ],
        }
    }
    if (!env.production) {
        conf.devServer = {
            contentBase: resolve(__dirname, 'public'),
            hot: true,
            port: 3000,
            publicPath: '/',
            headers: {
                "Access-Control-Allow-Origin": "*",
            },
        };
        conf.entry = [
            'react-hot-loader/patch',
            // activate HMR for React

            'webpack-dev-server/client?http://localhost:3000',
            // bundle the client for webpack-dev-server
            // and connect to the provided endpoint

            'webpack/hot/only-dev-server',
            // bundle the client for hot reloading
            // only- means to only hot reload for successful updates
        ].concat(['./app.js']);
        conf.plugins = [
            new webpack.HotModuleReplacementPlugin(),
            // enable HMR globally

            new webpack.NamedModulesPlugin(),
            // prints more readable module names in the browser console on HMR updates

            new webpack.NoEmitOnErrorsPlugin(),
            // do not emit compiled assets that include errors
            new webpack.DefinePlugin({
                PRODUCTION: JSON.stringify(false)
            }),
            new webpack.ProvidePlugin({
                jQuery: 'jquery',
                $: 'jquery',
                jquery: 'jquery'
            })

        ];
        conf.module.loaders.push(
            {
                test: /\.sass/,
                loader: 'style-loader!css-loader?sourceMap!sass-loader?sourceMap' //postcss-loader!
            }
        );

    } else {
        conf.entry = ['./app.js'];
        conf.output = {
            filename: 'bundle-[hash].min.js',
            path: path.resolve(__dirname, env.production ? 'dist/' : 'public/'),
            publicPath: env.production ? publicPath : ""
        };
        conf.plugins = [
            new ExtractTextPlugin({filename: 'bundle-[hash].css', allChunks: true}),

            new webpack.ProvidePlugin({
                jQuery: 'jquery',
                $: 'jquery',
                jquery: 'jquery'

            }),
            new webpack.DefinePlugin({
                PRODUCTION: JSON.stringify(true),
                'process.env': {
                    NODE_ENV: JSON.stringify('production')
                },
            }),


            new AssetsPlugin({path: path.join(__dirname, 'dist')}),
            new CleanWebpackPlugin(['dist'], {
                root: resolve(__dirname, ''),
                verbose: true,
                dry: false
            }),
            new webpack.ContextReplacementPlugin(/moment[\\/]locale$/, /^\.\/(pl)$/),
            //new BundleAnalyzerPlugin()

        ]

        if (!env.watch)
            conf.plugins.push(new webpack.optimize.UglifyJsPlugin({comments: false, minimize: true, sourceMap: true}))

        conf.module.loaders.push(
            {test: [/\.sass/, /\.scss/], loader: ExtractTextPlugin.extract('css-loader?soudMap!sass-loader?sourceMap')} //postcss-loader!
        );


    }

    //watch: true

    return conf;
}
