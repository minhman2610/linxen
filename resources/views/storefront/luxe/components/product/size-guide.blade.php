{{-- ================= SIZE HELP – LIN XÉN ================= --}}

<div class="lx-size-help">

    <div class="lx-size-help-header">

        <div class="lx-size-help-title">
            📏 Chọn size chuẩn
        </div>

        <div class="lx-size-help-actions">

            <button
                class="lx-size-btn"
                type="button"
                data-size-toggle
            >
                Gợi ý size nhanh
            </button>

            <button
                class="lx-size-btn outline"
                type="button"
                data-size-guide-open
            >
                Xem bảng size
            </button>

        </div>

    </div>


    {{-- QUICK SIZE FORM --}}
    <div class="lx-size-panel" hidden>

        <div class="lx-size-form">

            <input
                type="number"
                id="size-height"
                placeholder="Chiều cao (cm)"
            >

            <input
                type="number"
                id="size-weight"
                placeholder="Cân nặng (kg)"
            >

            <input
                type="number"
                id="size-bust"
                placeholder="Ngực (cm)"
            >

            <input
                type="number"
                id="size-waist"
                placeholder="Eo (cm)"
            >

            <input
                type="number"
                id="size-hip"
                placeholder="Mông (cm)"
            >

        </div>

        <button
            class="lx-size-submit"
            id="size-submit"
        >
            Gợi ý size phù hợp
        </button>

        <div
            class="lx-size-result"
            id="size-result"
            hidden
        ></div>

    </div>

</div>



{{-- ================= SIZE GUIDE MODAL ================= --}}

<div class="lx-size-guide-overlay" data-size-guide-overlay hidden>

    <div class="lx-size-guide-modal">

        <button
            class="lx-size-guide-close"
            data-size-guide-close
            aria-label="Đóng"
        >
            ✕
        </button>

        <h3 class="lx-size-guide-title">
            Bảng size LIN XÉN
        </h3>

        <p class="lx-size-guide-note">
            * Số đo trong bảng là <strong>số đo cơ thể</strong>, không phải số đo quần áo.
        </p>


        <div class="lx-size-guide-content">

            {{-- TABLE --}}
            <div class="lx-size-guide-table-wrap">

                <table class="lx-size-guide-table">

                    <thead>
                        <tr>
                            <th>SIZE</th>
                            <th>S</th>
                            <th>M</th>
                            <th>L</th>
                            <th>XL</th>
                        </tr>
                    </thead>

                    <tbody>

                        <tr>
                            <td>Chiều cao (cm)</td>
                            <td>153 – 160</td>
                            <td>155 – 165</td>
                            <td>160 – 170</td>
                            <td>160 – 170</td>
                        </tr>

                        <tr>
                            <td>Cân nặng (kg)</td>
                            <td>43 – 49</td>
                            <td>50 – 56</td>
                            <td>57 – 62</td>
                            <td>63 – 68</td>
                        </tr>

                        <tr>
                            <td>Vai (cm)</td>
                            <td>35</td>
                            <td>36</td>
                            <td>37</td>
                            <td>38</td>
                        </tr>

                        <tr>
                            <td>Ngực (cm)</td>
                            <td>82 – 85</td>
                            <td>86 – 89</td>
                            <td>90 – 93</td>
                            <td>94 – 97</td>
                        </tr>

                        <tr>
                            <td>Eo (cm)</td>
                            <td>64 – 67</td>
                            <td>68 – 71</td>
                            <td>72 – 75</td>
                            <td>76 – 79</td>
                        </tr>

                        <tr>
                            <td>Mông (cm)</td>
                            <td>88 – 91</td>
                            <td>92 – 95</td>
                            <td>96 – 99</td>
                            <td>100 – 103</td>
                        </tr>

                    </tbody>

                </table>

            </div>


            {{-- GUIDE --}}
            <div class="lx-size-guide-illustration">

                <ul>
                    <li><strong>Ngực:</strong> đo vòng nở nhất</li>
                    <li><strong>Eo:</strong> đo chỗ nhỏ nhất</li>
                    <li><strong>Mông:</strong> đo vòng lớn nhất</li>
                </ul>

            </div>

        </div>

    </div>

</div>