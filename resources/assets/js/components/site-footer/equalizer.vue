<template>
    <div id="equalizer">
        <div class="presets">
            <label class="select-wrapper" @change="loadPreset">
                <select v-model="selectedPresetIndex">
                    <option value="-1">Preset</option>
                    <option v-for="preset in presets" :value="$index">{{* preset.name }}</option>
                </select>
            </label>
        </div>
        <div class="bands">
            <span class="band preamp">
                <input
                    type="range"
                    min="-20"
                    max="20"
                    step="0.01"
                    data-orientation="vertical"
                    v-model="preampGainValue">
                <label>Preamp</label>
            </span>

            <span class="indicators">
                <span>+20</span>
                <span>0</span>
                <span>-20</span>
            </span>

            <span class="band amp" v-for="band in bands">
                <input
                    type="range"
                    min="-20"
                    max="20"
                    step="0.01"
                    data-orientation="vertical"
                    :value="band.filter.gain.value">
                <label>{{* band.label }}</label>
            </span>
        </div>
    </div>
</template>

<script>
    import _ from 'lodash';
    import $ from 'jquery';
    import rangeslider from 'rangeslider.js';

    import equalizerStore from '../../stores/equalizer';
    import utils from '../../services/utils';

    export default {
        data() {
            return {
                bands: [],
                selectedPresetIndex: -1,
                preampGainValue: 0,

                presets: equalizerStore.presets,
            };
        },

        methods: {
            /**
             * Init the equalizer.
             *
             * @param  {Element} player The audio player's DOM.
             */
            init(player) {
                let settings = equalizerStore.get();

                let AudioContext = window.AudioContext || window.webkitAudioContext || false;

                if (!AudioContext) {
                    return;
                }

                let context = new AudioContext();

                this.preampGainNode = context.createGain();
                this.changePreampGain(settings.preamp);

                let source = context.createMediaElementSource(player);
                source.connect(this.preampGainNode);

                let prevFilter = null;

                // Create 10 bands with the frequencies similar to those of Winamp and connect them together.
                [60, 170, 310, 600, 1000, 3000, 6000, 12000, 14000, 16000].forEach((f, i) => {
                    let filter = context.createBiquadFilter();

                    if (i === 0) {
                        filter.type = 'lowshelf';
                    } else if (i === 9) {
                        filter.type = 'highshelf';
                    } else {
                        filter.type = 'peaking';
                    }

                    filter.gain.value = settings.gains[i] ? settings.gains[i] : 0;
                    filter.Q.value = 1;
                    filter.frequency.value = f;

                    if (!prevFilter) {
                        this.preampGainNode.connect(filter);
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

                this.$nextTick(this.createRangeSliders);
            },

            /**
             * Create the UI slider for both the preamp and the normal bands using rangeslider.js.
             */
            createRangeSliders() {
                $('#equalizer input[type="range"]').each((i, el) => {
                    $(el).rangeslider({
                        /**
                         * Force the polyfill and its styles on all browsers.
                         *
                         * @type {Boolean}
                         */
                        polyfill: false,

                        /**
                         * Change the gain/preamp value when the user drags the sliders.
                         *
                         * @param  {Float} position
                         * @param  {Float} value
                         */
                        onSlide: (position, value) => {
                            if ($(el).parents('.band').is('.preamp')) {
                                this.changePreampGain(value);
                            } else {
                                this.changeFilterGain(this.bands[i - 1].filter, value);
                            }
                        },

                        /**
                         * Save the settings and set the preset index to -1 (which is None) on slideEnd.
                         */
                        onSlideEnd: () => {
                            this.selectedPresetIndex = -1;
                            this.save();
                        }
                    });
                });
            },

            /**
             * Change the gain value for the preamp.
             *
             * @param  {Number} dbValue The value of the gain, in dB.
             */
            changePreampGain(dbValue) {
                this.preampGainValue = dbValue;
                this.preampGainNode.gain.value = Math.pow(10, dbValue / 20);
            },

            /**
             * Change the gain value for a band/filter.
             *
             * @param  {Object} filter The filter object
             * @param  {Object} value  Value of the gain, in dB.
             */
            changeFilterGain(filter, value) {
                filter.gain.value = value;
            },

            /**
             * Load a preset when the user select it from the dropdown.
             */
            loadPreset() {
                if (Number.parseInt(this.selectedPresetIndex, 10) === -1) {
                    return;
                }

                let preset = this.presets[this.selectedPresetIndex];

                $('#equalizer input[type=range]').each((i, input) => {
                    // We treat our preamp slider differently.
                    if ($(input).parents('.band').is('.preamp')) {
                        this.changePreampGain(preset.preamp);
                    } else {
                        this.changeFilterGain(this.bands[i - 1].filter, preset.gains[i - 1]);
                        input.value = preset.gains[i - 1];
                    }
                });

                this.$nextTick(() => {
                    // Update the slider values into GUI.
                    $('#equalizer input[type="range"]').rangeslider('update', true);
                });

                this.save();
            },

            /**
             * Save the current user's equalizer preferences into local storage.
             */
            save() {
                equalizerStore.set(this.preampGainValue, _.pluck(this.bands, 'filter.gain.value'));
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

<style lang="sass">
    @import "../../../sass/partials/_vars.scss";
    @import "../../../sass/partials/_mixins.scss";

    #equalizer {
        position: absolute;
        bottom: $footerHeight;
        width: 100%;
        background: rgba(0, 0, 0, 0.9);
        display: flex;
        flex-direction: column;
        left: 0;

        label {
            margin-top: 8px;
            margin-bottom: 0;
            text-align: left;
        }

        .presets {
            padding: 8px 16px;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            flex: 1;
            align-content: center;
            z-index: 1;
            border-bottom: 1px solid rgba(255, 255, 255, .1);


            .select-wrapper {
                position: relative;
                margin-bottom: 0;

                &::after {
                    content: '\f107';
                    font-family: FontAwesome;
                    color: $colorHighlight;
                    display: inline-block;
                    position: absolute;
                    right: 8px;
                    top: 3px;
                    pointer-events: none;
                }
            }

            select {
                background: none;
                color: $colorMainText;
                padding-left: 0;
                width: 100px;
                text-transform: none;

                option {
                    color: #333;
                }
            }
        }

        .bands {
            padding: 16px;
            z-index: 1;
            left: 0;
            display: flex;
            justify-content: space-between;
            font-size: 70%;
            align-items: flex-start;

            .band {
                display: flex;
                flex-direction: column;
                align-items: center;
            }

            .indicators {
                height: 100px;
                width: 20px;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                align-items: center;
                margin-left: -16px;
                opacity: 0;
                transition: .4s;

                span:first-child {
                    line-height: 8px;
                }

                span:last-child {
                    line-height: 8px;
                }
            }

            &:hover .indicators {
                opacity: 1;
            }
        }

        /**
         * The range slider styles
         */
        .rangeslider {
            background: transparent;
            box-shadow: none;

            &--vertical {
                min-height: 100px;
                width: 16px;

                &::before {
                    content: " ";
                    position: absolute;
                    left: 7px;
                    width: 2px;
                    background: rgba(255, 255, 255, 0.2);
                    z-index: 1;
                    height: 100%;
                    pointer-events: none;
                }

                .rangeslider__fill {
                    width: 2px;
                    background: #fff;
                    position: absolute;
                    left: 7px;
                    box-shadow: none;
                    border-radius: 0;
                }

                .rangeslider__handle {
                    left: 0;
                    background: #fff;
                    border: 0;
                    height: 2px;
                    width: 100%;
                    border-radius: 0;
                    box-shadow: none;

                    &::after {
                        display: none;
                    }
                }
            }
        }

        @media only screen and (max-device-width : 768px) {
            position: fixed;
            max-width: 414px;
            left: auto;
            right: 0;
            bottom: $footerHeight + 5px;
            display: block;
            height: auto;

            label {
                line-height: 20px;
            }
        }
    }
</style>
