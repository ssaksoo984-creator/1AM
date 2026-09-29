# 1AM — 시안 1 (Vivid)

1AM Slim HYBRID 브랜드 사이트 **워드프레스 테마** 시안 1.
fogformulas.com 처럼 맛마다 화면 전체 컬러가 바뀌고, 스크롤에 반응하는 인터랙션을 넣은 비비드 컨셉입니다.

```
1am-vivid/          ← 워드프레스 테마 (이 폴더를 wp-content/themes/ 에 업로드)
dist/1am-vivid.zip  ← 관리자 > 외모 > 테마 > 새로 추가 > 테마 업로드 용 zip
preview/index.html  ← 워드프레스 없이 브라우저로 열어보는 정적 미리보기
tools/              ← 미리보기 생성 스크립트
```

## 설치

1. 워드프레스 관리자 → **외모 > 테마 > 새로 추가 > 테마 업로드** → `dist/1am-vivid.zip` 업로드 → 활성화
2. 바로 메인에 15개 맛이 나옵니다 (테마 기본 데이터).
3. 필요하면 **외모 > 사용자 정의하기 > 1AM 설정** 에서 문구 수정.

## 메인 페이지 구성 (견적서 기준)

| 순서 | 섹션 | 내용 / 인터랙션 |
|---|---|---|
| – | 상단 고정 경고문 | 항상 보이는 니코틴 경고 띠 (문구는 사용자 정의하기에서 수정) |
| – | 연령 확인 | 첫 방문 시 19+ 확인 팝업 |
| 1 | 메인 비주얼 | 맛 슬라이더 + 색 전환, **도매 가입 / 상품 보기 버튼** |
| 2 | 흐르는 띠 | 스크롤 속도 반응 마퀴 |
| 3 | 영상 | 스크롤하면 카드 → 화면 전체로 커짐. MP4 업로드 또는 YouTube 주소 |
| 4 | 회사 소개 | 단어별로 진해지는 타이틀 + 숫자 카운트 |
| 5 | **상품 3종 (가로 스크롤)** | Slim HYBRID 2ml (판매 중) / HYBRID Max 대용량 (출시 예정) / HYBRID Refill 리필 (출시 예정) |
| 6 | Slim HYBRID 맛 | 필터 + 카드 호버 |
| 7 | 디바이스 | 핀 고정 + 제품/색 전환 + 스펙 |
| 8 | Why stock 1AM | 도매 거래처 장점 4개 |
| 9 | 구매 절차 | 가입 → 승인 → 주문 (선이 그려지는 스텝) |
| 10 | FAQ | 아코디언 5개 + 전체 FAQ 링크 |
| 11 | 도매 가입 유도 | 펼쳐지는 제품 + 채워지는 타이틀 + 가입/로그인 버튼 |

**회원 상태별 버튼** (메인·상품 페이지·메뉴·푸터 공통): 비회원 → `Apply for wholesale` / 승인 대기 → `Application under review` / 승인 완료 → `Shop wholesale`.
승인 판단: 사용자 메타 `oneam_wholesale_status = approved` 또는 `wholesale_customer` 역할. 가입 승인 플러그인을 쓰면 `oneam_member_state` 필터로 연결합니다.

**메뉴 구조**: Home / Products (Slim HYBRID · HYBRID Max · HYBRID Refill) / About Us / How to Order / FAQ / Wholesale (Apply · Log in · Shop). 푸터: Explore · Wholesale · Policies(이용약관 · 개인정보 · 배송/반품).

**WooCommerce**: 승인 회원이 아니면 상점·장바구니·결제 접근 시 가입(또는 승인 대기) 페이지로 이동, 쇼핑몰은 검색 노출 제외(noindex). 쇼핑몰 페이지에서는 부드러운 스크롤·커스텀 커서를 끄고 `woo.css` 로 브랜드 스타일만 입힙니다.

## 만들어야 할 페이지 (관리자 > 페이지)

| 페이지 | 슬러그 | 템플릿 |
|---|---|---|
| About Us | `about-us` | About Us |
| How to Order | `how-to-order` | How to Order (구매 절차) |
| FAQ | `faq` | 기본 — 질문마다 **세부 정보(Details)** 블록 사용 |
| Wholesale Sign up | `wholesale-signup` | 기본 — 가입 승인 플러그인의 가입 폼 숏코드 |
| Terms / Privacy / Shipping & Returns | `terms` · `privacy-policy` · `shipping-returns` | 기본 |

## 워드프레스에서 관리하기

- **상품(Products)**: 관리자 좌측 **Products** 메뉴 — 이름, 짧은 라벨(2ml Disposable), 상태(판매 중/출시 예정), 컬러, 스펙("이름: 값" 한 줄씩), 대표 이미지. 이미지가 없으면 출시 예정 실루엣이 나옵니다. 상품 주소 `/products/{slug}/`.
- **맛(Flavors)**: 관리자 좌측 **Flavors** 메뉴 (상품 라인 슬러그로 상품 페이지에 연결)
  - 제목 = 맛 이름, 요약 = 한 줄 설명, 본문 = 상세 페이지 내용
  - **대표 이미지** = 배경 투명한 제품 이미지 (세로형)
  - 우측 박스에서 **메인/서브 컬러**, **카테고리** 지정
  - **순서(page attributes)** 로 노출 순서 조정. 앞의 5개가 Flavor Lab / Device 섹션에 나옵니다.
  - Flavors 글이 하나라도 등록되면 테마 기본 데이터 대신 등록한 글만 사용합니다.
  - 등록 시 슬러그를 `grape-ice` 처럼 두면 대표 이미지가 없을 때 테마 내장 이미지를 씁니다.
- **메뉴**: 외모 > 메뉴 → `메인 메뉴 (전체화면 메뉴)`, `푸터 메뉴` 위치 지정 (없으면 기본 앵커 링크)
- **로고**: 사용자 정의하기 > 사이트 아이덴티티 > 로고 (헤더용 검정 로고)
- **문구**: 사용자 정의하기 > 1AM 설정 (히어로, 마퀴, 스펙 숫자, CTA 링크, 경고 문구, 성인 인증)
- 맛 상세 페이지 `/flavor/{slug}/`, 전체 목록 `/flavors/` — 활성화 후 **설정 > 고유주소 > 저장** 한 번 눌러주세요.

## 참고 / 확인 필요

- 회사 소개, 장점 4개, FAQ 답변, 상품 2·3 이름(HYBRID Max / HYBRID Refill)과 스펙, 디바이스 콜아웃은 **임시 문구**입니다.
- 상단 경고문 문구는 Health Canada 기준으로 최종 확인이 필요합니다.
- 맛별 컬러는 제품 이미지에서 뽑아 채도를 올린 값입니다. 관리자에서 바로 바꿀 수 있습니다.
- 라이브러리(GSAP 3.15, ScrollTrigger, Lenis 1.3)와 폰트(Archivo, Instrument Serif)는 테마 안에 포함되어 있어 외부 CDN 없이 동작합니다.

## 미리보기 다시 만들기

```bash
php tools/preview-shim.php 1am-vivid front-page.php > preview/index.html
```
