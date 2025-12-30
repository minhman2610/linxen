{{-- ================= SIZE ASSISTANT ================= --}}
<div class="lx-size-assistant">

    <h4 class="lx-size-assistant-title">
        Trợ lý chọn size
    </h4>

    <p class="lx-size-assistant-note">
        Nhập số đo cơ thể để LIN XÉN gợi ý size phù hợp nhất cho bạn.
    </p>

    <div class="lx-size-form">

        <div class="lx-size-field">
            <label>Chiều cao (cm)</label>
            <input type="number" id="size-height" placeholder="VD: 160">
        </div>

        <div class="lx-size-field">
            <label>Cân nặng (kg)</label>
            <input type="number" id="size-weight" placeholder="VD: 52">
        </div>

        <div class="lx-size-field">
            <label>Vòng ngực (cm)</label>
            <input type="number" id="size-bust" placeholder="VD: 86">
        </div>

        <div class="lx-size-field">
            <label>Vòng eo (cm)</label>
            <input type="number" id="size-waist" placeholder="VD: 68">
        </div>

        <div class="lx-size-field">
            <label>Vòng mông (cm)</label>
            <input type="number" id="size-hip" placeholder="VD: 92">
        </div>

    </div>

    <button class="lx-size-submit" id="size-submit">
        Gợi ý size phù hợp
    </button>

    <div class="lx-size-result" id="size-result" hidden></div>

</div>

{{-- ================= SIZE GUIDE – LIN XÉN ================= --}}

{{-- SIZE GUIDE TRIGGER --}}
<div class="lx-size-guide-cta" data-size-guide-open>
    <span class="lx-size-guide-icon">📏</span>
    <div class="lx-size-guide-text">
        <strong>Xem bảng size</strong>
        <small>Chọn size chuẩn dáng LIN XÉN</small>
    </div>
    <span class="lx-size-guide-arrow">›</span>
</div>


{{-- MODAL --}}
<div class="lx-size-guide-overlay" data-size-guide-overlay hidden>
    <div class="lx-size-guide-modal">

        <button class="lx-size-guide-close" data-size-guide-close aria-label="Đóng">
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
        <td>
            <div class="lx-range">
                <span>153</span>
                <span class="lx-range-mid">–</span>
                <span>160</span>
            </div>
        </td>
        <td>
            <div class="lx-range">
                <span>155</span>
                <span class="lx-range-mid">–</span>
                <span>165</span>
            </div>
        </td>
        <td>
            <div class="lx-range">
                <span>160</span>
                <span class="lx-range-mid">–</span>
                <span>170</span>
            </div>
        </td>
        <td>
            <div class="lx-range">
                <span>160</span>
                <span class="lx-range-mid">–</span>
                <span>170</span>
            </div>
        </td>
    </tr>

    <tr>
        <td>Cân nặng (kg)</td>
        <td>
            <div class="lx-range">
                <span>43</span>
                <span class="lx-range-mid">–</span>
                <span>49</span>
            </div>
        </td>
        <td>
            <div class="lx-range">
                <span>50</span>
                <span class="lx-range-mid">–</span>
                <span>56</span>
            </div>
        </td>
        <td>
            <div class="lx-range">
                <span>57</span>
                <span class="lx-range-mid">–</span>
                <span>62</span>
            </div>
        </td>
        <td>
            <div class="lx-range">
                <span>63</span>
                <span class="lx-range-mid">–</span>
                <span>68</span>
            </div>
        </td>
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
        <td>
            <div class="lx-range">
                <span>82</span>
                <span class="lx-range-mid">–</span>
                <span>85</span>
            </div>
        </td>
        <td>
            <div class="lx-range">
                <span>86</span>
                <span class="lx-range-mid">–</span>
                <span>89</span>
            </div>
        </td>
        <td>
            <div class="lx-range">
                <span>90</span>
                <span class="lx-range-mid">–</span>
                <span>93</span>
            </div>
        </td>
        <td>
            <div class="lx-range">
                <span>94</span>
                <span class="lx-range-mid">–</span>
                <span>97</span>
            </div>
        </td>
    </tr>

    <tr>
        <td>Eo (cm)</td>
        <td>
            <div class="lx-range">
                <span>64</span>
                <span class="lx-range-mid">–</span>
                <span>67</span>
            </div>
        </td>
        <td>
            <div class="lx-range">
                <span>68</span>
                <span class="lx-range-mid">–</span>
                <span>71</span>
            </div>
        </td>
        <td>
            <div class="lx-range">
                <span>72</span>
                <span class="lx-range-mid">–</span>
                <span>75</span>
            </div>
        </td>
        <td>
            <div class="lx-range">
                <span>76</span>
                <span class="lx-range-mid">–</span>
                <span>79</span>
            </div>
        </td>
    </tr>

    <tr>
        <td>Mông (cm)</td>
        <td>
            <div class="lx-range">
                <span>88</span>
                <span class="lx-range-mid">–</span>
                <span>91</span>
            </div>
        </td>
        <td>
            <div class="lx-range">
                <span>92</span>
                <span class="lx-range-mid">–</span>
                <span>95</span>
            </div>
        </td>
        <td>
            <div class="lx-range">
                <span>96</span>
                <span class="lx-range-mid">–</span>
                <span>99</span>
            </div>
        </td>
        <td>
            <div class="lx-range">
                <span>100</span>
                <span class="lx-range-mid">–</span>
                <span>103</span>
            </div>
        </td>
    </tr>
</tbody>

                </table>
            </div>

            {{-- ILLUSTRATION --}}
            <div class="lx-size-guide-illustration">
                
                <ul>
                    <li><strong>Ngực</strong>: đo vòng nở nhất</li>
                    <li><strong>Eo</strong>: đo chỗ nhỏ nhất</li>
                    <li><strong>Mông</strong>: đo vòng lớn nhất</li>
                </ul>
            </div>

        </div>
    </div>
</div>
