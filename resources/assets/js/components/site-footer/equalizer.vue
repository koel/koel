<template>
    <div id="equalizer">
        <div class="presets">
            <button v-for="preset in presets" @click.prevent="loadPreset(preset)">{{ preset.name }}</button>
        </div>
        <div class="bands">
            <span class="band" v-for="band in bands">
                <label>{{ band.label }}</label>
                <input type="range" min="-20" max="20" step="0.01" :value="band.filter.gain.value" orient="vertical" 
                    @input="changeGain(band.filter, $event)">
            </span>
        </div>
    </div>
</template>

<script>
    import _ from 'lodash';
    import $ from 'jquery';

    import preferenceStore from '../../stores/preference';
    import utils from '../../services/utils';

    export default {
        data() {
            return {
                bands: [],

                presets: [
                    {
                        name: 'None',
                        gains: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    },
                    {
                        name: 'Classical',
                        gains: [-1, -1, -1, -1, -1, -1, -7, -7, -7, -9],
                    },
                    {
                        name: 'Club',
                        gains: [-1, -1, 8, 5, 5, 5, 3, -1, -1, -1],
                    },
                    {
                        name: 'Dance',
                        gains: [9, 7, 2, -1, -1, -5, -7, -7, -1, -1],
                    },
                    {
                        name: 'Full Bass',
                        gains: [-8, 9, 9, 5, 1, -4, -8, -10, -11, -11]
                    },
                    {
                        name: 'Full Treble',
                        gains: [-9, -9, -9, -4, 2, 11, 16, 16, 16, 16]
                    },
                    {
                        name: 'Headphone',
                        gains: [4, 11, 5, -3, -2, 1, 4, 9, 12, 14]
                    },
                    {
                        name: 'Large Hall',
                        gains: [10, 10, 5, 5, -1, -4, -4, -4, -1, -1],
                    },
                    {
                        name: 'Live',
                        gains: [-4, -1, 4, 5, 5, 5, 4, 2, 2, 2],
                    },
                    {
                        name: 'Pop',
                        gains: [-1, 4, 7, 8, 5, -1, -2, -2, -1, -1],
                    },
                    {
                        name: 'Reggae',
                        gains: [-1, -1, -1, -5, -1, 6, 6, -1, -1, -1],
                    },
                    {
                        name: 'Rock',
                        gains: [8, 4, -5, -8, -3, 4, 8, 11, 11, 11],
                    },
                    {
                        name: 'Soft Rock',
                        gains: [4, 4, 2, -1, -4, -5, -3, -1, 2, 8],
                    },
                    {
                        name: 'Techno',
                        gains: [8, 5, -1, -5, -4, -1, 8, 9, 9, 8],
                    },
                ],
            };
        },

        methods: {
            /**
             * Init the equalizer.
             * 
             * @param  {Object} player The audio player DOM.
             */
            init(player) {
                var AudioContext = window.AudioContext || window.webkitAudioContext || false; 
                var context = new AudioContext();

                var gainNode = context.createGain();
                gainNode.gain.value = 1;

                var source = context.createMediaElementSource(player);
                source.connect(gainNode);

                var prevFilter = null;

                var savedGains = preferenceStore.get('equalizerGains');

                // Create 10 bands with the frequencies similar to those of Winamp and connect them together.
                [60, 170, 310, 600, 1000, 3000, 6000, 12000, 14000, 16000].forEach((f, i) => {
                    var filter = context.createBiquadFilter();

                    filter.type = 'peaking';
                    filter.gain.value = savedGains[i];
                    filter.Q.value = 1;
                    filter.frequency.value = f;

                    if (!prevFilter) {
                        gainNode.connect(filter);
                    } else {
                        prevFilter.connect(filter);
                    }

                    prevFilter = filter;

                    this.bands.push({
                        filter,
                        label: (f + '').replace('000', 'K'),
                    });
                });
                
                prevFilter.connect(context.destination);
            },

            /**
             * Change the gain value for a band/filter on range input's value change.
             * 
             * @param  {Object} filter The filter object.
             * @param  {Object} e      The input event
             */
            changeGain(filter, e) {
                filter.gain.value = e.target.value;
                this.save();
            },

            /**
             * Load a preset.
             * 
             * @param  {Object} preset The preset.
             */
            loadPreset(preset) {
                $('#equalizer input[type=range]').each((i, input) => {
                    this.bands[i].filter.gain.value = preset.gains[i];
                    input.value = preset.gains[i];
                });

                this.save();
            },

            /**
             * Save the current user's equalizer preferences into local storage.
             */
            save() {
                preferenceStore.set('equalizerGains', _.pluck(this.bands, 'filter.gain.value'));
            },
        },

        events: {
            'equalizer:init': function (player) {
                if (utils.isAudioContextSupported()) {
                    this.init(player);
                }
            },
        },
    };
</script>

<style lang="sass" scoped>
    @import "resources/assets/sass/partials/_vars.scss";
    @import "resources/assets/sass/partials/_mixins.scss";

    #equalizer {
        position: absolute;
        bottom: $footerHeight;
        height: 256px;
        width: 100%;
        padding: 16px;
        background: rgba(0, 0, 0, 0.9);
        display: flex;
        flex-direction: column;
        left: 0;

        .presets {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            margin-bottom: 8px;
            flex: 1;
            align-content: center;

            button {
                font-size: 70%;
                border-radius: 0;
                text-transform: uppercase;
                background-color: transparent;
                border: 1px solid rgba(255, 255, 255, .2);
                margin-bottom: 4px;

                &:hover {
                    background-color: $colorRed;
                }
            }
        }

        .bands {
            display: flex;
            left: 0;
            justify-content: space-between;
            font-size: 70%;
            align-items: center;

            input[type="range"] {
                writing-mode: bt-lr; /* IE */
                -webkit-appearance: slider-vertical; /* WebKit */
                width: 8px;
                height: 80px;
            }

            .band {
                display: flex;
                flex-direction: column;
                align-items: center;
            }
        }
    }
</style>
